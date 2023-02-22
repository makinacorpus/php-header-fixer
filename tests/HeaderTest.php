<?php

namespace MakinaCorpus\HeaderFixer\Tests;

use MakinaCorpus\HeaderFixer\Header;
use PHPUnit\Framework\TestCase;

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

    public function testAttributePreservation()
    {
        $input = <<<EOT
<h2     class="foo">1 (first)</h2>
<p>some noise</p>

<h1>1 (second)</h2>
<p>some noise</p>

<h4>1 (third)</h2>
<p>some noise</p>
EOT;

        $headers = Header::fixText($input);
        $this->assertSame(trim(<<<EOT
<h1 class="foo">1 (first)</h1>
<p>some noise</p>

<h1>1 (second)</h1>
<p>some noise</p>

<h2>1 (third)</h2>
<p>some noise</p>
EOT
            ),
            $headers->getText()
        );
    }

    public function testAddId()
    {
        $input = <<<EOT
<h1 class="foo">1 (first)</h2>
EOT;

        $headers = Header::fixText($input, 0, false, true);
        $this->assertSame(trim(<<<EOT
<h1 id="section-1-0" class="foo">1 (first)</h1>
EOT
            ),
            $headers->getText()
        );

        $headers = Header::fixText($input, 0, false, true, 'foo');
        $this->assertSame(trim(<<<EOT
<h1 id="foo1-0" class="foo">1 (first)</h1>
EOT
            ),
            $headers->getText()
        );
    }

    public function testEverything()
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

<h42>will become 3 (or 4 without orphans)</h42>
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
1.2.3.42 -> 1.2.3.4
1 -> 1
1.2 -> 1.2
1.2.3 -> 1.2.3
1.2.3.4 -> 1.2.3.4
1.2 -> 1.2
EOT
            ),
            trim($this->buildRepresentation($headers))
        );

        $headers = Header::find($input);
        $headers->fix(0, false);
        $this->assertSame(trim(<<<EOT
1 -> 1
1.2 -> 1.2
1.2.3 -> 1.2.3
1.2.3 -> 1.2.3
1.2.3.42 -> 1.2.3.4
1 -> 1
1.2 -> 1.2
1.2.3 -> 1.2.3
1.2.3.4 -> 1.2.3.4
1.2 -> 1.2
EOT
            ),
            trim($this->buildRepresentation($headers))
        );

        $headers = Header::find($input);
        $headers->fix(0, true);
        $this->assertSame(trim(<<<EOT
1 -> 1
2 -> 1
2.3 -> 1.2
2.3 -> 1.2
2.42 -> 1.2
1 -> 1
1.2 -> 1.2
1.3 -> 1.2
1.4 -> 1.2
1.2 -> 1.2
EOT
            ),
            trim($this->buildRepresentation($headers))
        );

        $this->assertSame(trim(<<<EOT
<p>some noise</p>

<h1>1 (first)</h1>
<p>some noise</p>

<h2>lonely h2 will become 1</h2>
<p>some noise</p>

<h3>will become 2 (first)</h3>
<p>some noise</p>

<h3>will become 2 (second)</h3>
<p>some noise</p>

<h4>will become 3 (or 4 without orphans)</h4>
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
EOT
            ),
            trim(Header::fixText($input, 0, false))
        );

        $this->assertSame(trim(<<<EOT
<p>some noise</p>

<h1>1 (first)</h1>
<p>some noise</p>

<h1>lonely h2 will become 1</h1>
<p>some noise</p>

<h2>will become 2 (first)</h2>
<p>some noise</p>

<h2>will become 2 (second)</h2>
<p>some noise</p>

<h2>will become 3 (or 4 without orphans)</h2>
<p>some noise</p>

<h1>will remain 1 (second)</h1>
<p>some noise</p>

<h2>1.2 first</h2>
<p>some noise</p>

<h2>1.2 second</h2>
<p>some noise</p>

<h2>1.2 third</h2>
<p>some noise</p>

<h2>1.2 fourth</h2>
<p>some noise</p>
EOT
            ),
            trim(Header::fixText($input, 0, true))
        );

        $this->assertSame(trim(<<<EOT
<p>some noise</p>

<h6>1 (first)</h6>
<p>some noise</p>

<h7>lonely h2 will become 1</h7>
<p>some noise</p>

<h8>will become 2 (first)</h8>
<p>some noise</p>

<h8>will become 2 (second)</h8>
<p>some noise</p>

<h9>will become 3 (or 4 without orphans)</h9>
<p>some noise</p>

<h6>will remain 1 (second)</h6>
<p>some noise</p>

<h7>1.2 first</h7>
<p>some noise</p>

<h8>1.2 second</h8>
<p>some noise</p>

<h9>1.2 third</h9>
<p>some noise</p>

<h7>1.2 fourth</h7>
<p>some noise</p>
EOT
            ),
            trim(Header::fixText($input, 5, false))
        );

        $this->assertSame(trim(<<<EOT
<p>some noise</p>

<h3>1 (first)</h3>
<p>some noise</p>

<h3>lonely h2 will become 1</h3>
<p>some noise</p>

<h4>will become 2 (first)</h4>
<p>some noise</p>

<h4>will become 2 (second)</h4>
<p>some noise</p>

<h4>will become 3 (or 4 without orphans)</h4>
<p>some noise</p>

<h3>will remain 1 (second)</h3>
<p>some noise</p>

<h4>1.2 first</h4>
<p>some noise</p>

<h4>1.2 second</h4>
<p>some noise</p>

<h4>1.2 third</h4>
<p>some noise</p>

<h4>1.2 fourth</h4>
<p>some noise</p>
EOT
            ),
            trim(Header::fixText($input, 2, true))
        );
    }
}
