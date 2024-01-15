<?php

namespace MakinaCorpus\HeaderFixer;

final class TextWithHeader
{
    public function __construct(
        private Header $header,
        private string $text,
    ) {}

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
