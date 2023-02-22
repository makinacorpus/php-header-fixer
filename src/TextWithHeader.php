<?php

namespace MakinaCorpus\HeaderFixer;

final class TextWithHeader
{
    private $header;
    private $text;

    public function __construct(Header $header, string $text)
    {
        $this->header = $header;
        $this->text = $text;
    }

    public function getHeader(): Header
    {
        return $this->header;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function __toString(): string
    {
        return $this->text;
    }
}
