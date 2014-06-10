<?php
    require_once('includes/bootstrap.inc.php');
    require_once('includes/header.inc.php');

    $reference = (isset($_GET['ref']) && preg_match('/(\d+\.?)+/', $_GET['ref'])) ? $_GET['ref'] : '1';

    try {
        $section = $kss->getSection($reference);
    } catch (UnexpectedValueException $e) {
        $reference = '1';
        $section = $kss->getSection($reference);
    }
?>

<h1><?php echo $section->getTitle(); ?></h1>

<?php
    foreach ($kss->getSectionChildren($reference) as $section) {
        require('includes/block.inc.php');
    }
?>

<p>This block above was created with a simple php block:</p>
<pre><code>&lt;?php
    foreach ($kss->getSectionChildren($reference) as $section) {
        require('includes/block.inc.php');
    }
?&gt;</code></pre>
<p>
    Take a look at the source code for more details. The goal is to remove
    the pain from creating a styleguide — document your CSS, have example
    HTML in your templates and automate as much as possible.
</p>

<?php
    require_once('includes/footer.inc.php');
?>
