<?php

namespace MakinaCorpus\HeaderFixer\Tests;

use PHPUnit\Framework\TestCase;
use MakinaCorpus\HeaderFixer\Header;

class HeaderTest extends TestCase
{
    private function buildRepresentation(Header $header, bool $withText = false) : string
    {
        $output = '';
        /** @var \MakinaCorpus\HeaderFixer\Header $child */
        foreach ($header as $child) {
            $output .= "\n".$child->getUserRepresentation().' -> '.$child->getRealRepresentation().($withText ? ' : '.$child->getText() : '');
            $output .= $this->buildRepresentation($child);
        }
        return $output;
    }

    public function testRelocate()
    {
        $input = <<<EOT
<p>some noise</p>

<h1>1 (first)</h1>
<p>some noise</p>

<h2>lonely h2 will become 1</h2>
<p>some noise</p>

<h3>will become 2 (first)</h3>
<p>some noise</p>

<h3>will become 2 (second)</h3>
<p>some noise</p>

<h1>will remain 1 (second)</h1>
<p>some noise</p>

<h2>1.2 first</h2>
<p>some noise</p>

<h3>1.2 second</h3>
<p>some noise</p>

<h4>1.2 third</h4>
<p>some noise</p>

<h2>1.2 fourth</h2>
<p>some noise</p>
EOT;

        $headers = Header::find($input);

        $this->assertSame(trim(<<<EOT
1 -> 1
1.2 -> 1.2
1.2.3 -> 1.2.3
1.2.3 -> 1.2.3
1 -> 1
1.2 -> 1.2
1.2.3 -> 1.2.3
1.2.3.4 -> 1.2.3.4
1.2 -> 1.2
EOT
            ),
            trim($this->buildRepresentation($headers))
        );

        $headers->fix(0, true);
        $this->assertSame(trim(<<<EOT
1 -> 1
2 -> 1
2.3 -> 1.2
2.3 -> 1.2
1 -> 1
1.2 -> 1.2
1.3 -> 1.2
1.4 -> 1.2
1.2 -> 1.2
EOT
            ),
            trim($this->buildRepresentation($headers))
        );
    }
}
