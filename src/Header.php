<?php

namespace MakinaCorpus\HeaderFixer;

final class Header implements \IteratorAggregate, \Countable
{
    /**
     * Default identifier prefix if none given.
     */
    const ID_PREFIX = 'section-';

    /**
     * Regex that extract the full table of contents of an HTML input.
     */
    const HEADERS_REGEX = '/<h([\d]+)(.*?)>(.*?)<\/h[\d]+>/ims';

    private bool $hasId = false;
    private ?int $computedLevel = null;
    private ?Header $parent = null;
    private array $children = [];

    public function __construct(
        private int $offset = 0,
        private int $length = 0,
        private int $userLevel = 0,
        private string $text = '',
        private ?string $attributes = null
    ) {
        $this->offset = $offset;
        $this->length = $length;
        $this->userLevel = $userLevel;
        $this->text = $text;
        $this->attributes = $attributes ? (' ' . \trim($attributes)): '';

        if ($attributes && false !== \strpos($attributes, 'id=')) {
            $this->hasId = true;
        }
    }

    /**
     * Find all headers in text
     *
     * Returned object is a virtual root object that does not exist in text.
     */
    public static function find(string $text): Header
    {
        $matches = [];
        $parent = new Header();
        if (\preg_match_all(self::HEADERS_REGEX, $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $parent->append(
                    new Header(
                        attributes: $matches[2][$index][0],
                        length: \strlen($match[0]),
                        offset: (int) $match[1],
                        text: $matches[3][$index][0],
                        userLevel: (int) $matches[1][$index][0],
                    )
                );
            }
        }
        return $parent;
    }

    /**
     * Find all headers in text and fix semantical hierarchy
     *
     * @param string $text
     *   Original text to fix
     * @param int $delta
     *   Per default 0. If you need your headings to semantically start with h3
     *   intead of h1 for example, set 2 here, if you with to start with h2, set
     *   1, etc..
     * @param bool $relocateOrphans
     *   If set to true, when a heading is alone (has no siblings at the same level)
     *   exists then it's reattached to its parent in the tree.
     *
     * @return string
     *   Fixed text
     */
    public static function fixText(
        string $text,
        int $delta = 0,
        bool $relocateOrphans = false,
        bool $addId = false,
        ?string $idPrefix = null,
    ): TextWithHeader {
        $headers = self::find($text);

        $headers->fix(
            addId: $addId,
            delta: $delta,
            idPrefix: $idPrefix,
            relocateOrphans: $relocateOrphans,
        );

        foreach ($headers->getRecursiveReverseIterator() as $header) {
            $realLevel = $header->getRealLevel();
            $attributes = $header->getAttributes();
            $id = $addId ? ' id="' . $header->getId($idPrefix) . '"' : '';

            $text = substr_replace(
                $text,
                // Use a substring to ensure that if the string length change, we do
                // not squash existing text around or leave cruft in text.
                '<h'.$realLevel.$id.$attributes.'>'.$header->getText().'</h'.$realLevel.'>',
                $header->offset,
                $header->length
            );
        }

        return new TextWithHeader($headers, $text);
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getUserLevel(): int
    {
        return $this->userLevel;
    }

    /**
     * @return null|Header
     */
    public function getFirstChild()
    {
        foreach ($this->children as $child) {
            return $child;
        }
        return null;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->children);
    }

    /**
     * Get identifier for anchor
     */
    public function getId(?string $prefix = null): string
    {
        return ($prefix ?? self::ID_PREFIX) . $this->getUserRepresentation() . '-' . $this->getPosition();
    }

    /**
     * Get attributes
     */
    public function getAttributes(): string
    {
        return $this->attributes;
    }

    /**
     * Get resursive reverse iterator for proceeding to replacements
     *
     * @return Header[]
     */
    public function getRecursiveReverseIterator()
    {
        foreach (\array_reverse($this->children) as $child) {
            yield from $child->getRecursiveReverseIterator();
            yield $child;
        }
    }

    #[\Override]
    public function getIterator(): \Iterator
    {
        foreach ($this->children as $child) {
            yield $child;
        }
    }

    /**
     * Only in use at built time, append the child at the right place in the tree
     */
    private function append(Header $header)
    {
        if ($this->children) {
            $latest = \end($this->children);
            if ($latest->userLevel < $header->userLevel) {
                $latest->append($header);
                return;
            }
        }
        $this->children[] = $header;
        $header->parent = $this;
    }

    private function removeChild(Header $header)
    {
        $modified = false;

        foreach ($this->children as $position => $child) {
            if ($child === $header) {
                unset($this->children[$position]);
                $modified = true;
            }
        }

        if ($modified) {
            $this->children = \array_values($this->children); // Fix keys
        }

        if ($header->parent === $this) {
            $header->parent = null;
        }
    }

    private function addChild(Header $header, int $position = 0)
    {
        $this->removeChild($header);

        if (!$position || $position < 0) {
            \array_unshift($this->children, $header);
        } else if ($position > count($this->children)) {
            $this->children[] = $header;
        } else {
            \array_splice($this->children, $position, 0, [$header]);
        }

        $header->parent = $this;
    }

    private function getPosition(): int
    {
        if ($this->parent) {
            return (int)array_search($this, $this->parent->children);
        }
        return 0;
    }

    public function getUserRepresentation(string $separator = '.'): string
    {
        if ($this->parent && $this->parent->userLevel) { // Ignore top-level "0" level
            return $this->parent->getUserRepresentation().$separator.$this->userLevel;
        }
        return $this->userLevel;
    }

    public function getRealRepresentation(string $separator = '.'): string
    {
        if ($this->parent && $this->parent->userLevel) { // Ignore top-level "0" level
            return $this->parent->getRealRepresentation().$separator.$this->getRealLevel();
        }
        return $this->getRealLevel();
    }

    public function getRealLevel(int $delta = 0): int
    {
        if (null !== $this->computedLevel) {
            return $this->computedLevel;
        }

        if ($this->parent) {
            return $this->parent->getRealLevel($delta) + 1;
        }

        return $delta;
    }

    public function fix(
        int $delta = 0,
        bool $relocateOrphans = false,
        bool $addId = false,
        ?string $idPrefix = null,
    ) {
        // Start by relocating itself first: we can relocate ourselves only
        // if the current parent has a parent, because we are going to become
        // our own parent sibling, we must insert ourself somewhere
        if ($relocateOrphans) {
            if ($this->parent && $this->parent->parent && 1 === count($this->parent->children)) {
                $parent = $this->parent;
                $position = $parent->getPosition() + 1;
                $parent->removeChild($this);
                $parent->parent->addChild($this, $position);
            }
        }

        $this->computedLevel = $this->getRealLevel($delta);

        foreach ($this->children as $child) {
            \assert($child instanceof Header);

            $child->fix(
                addId: $addId,
                delta: $delta,
                idPrefix: $idPrefix,
                relocateOrphans: $relocateOrphans,
            );
        }
    }
}
