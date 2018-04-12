Header fixer
============

Fixes header semantic hierarchy in HTML text.

.. code-block:: php

    $fixedHtmlText = \MakinaCorpus\HeaderFixer\Header::fix($originalHtmlText, 0, true);

And that is pretty much it.

Options are:
 - ``0`` is the decal, if you, for example, want the text to start with
   ``h2`` instead of ``h1``, then set ``1`` here, for ``h3`` set ``2``, etc...
 - ``true`` is the *relocate orphans* options, if set to true, when a title is
   the single one at his own level, with no siblings, it will be put at a higher
   level side by side its parent.
