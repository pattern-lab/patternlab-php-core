<div class="styleguide" id="r<?php echo $section->getReference(); ?>">
    <h3 class="styleguide__header">
        <span class="styleguide__reference"><?php echo $section->getReference(); ?></span>
        <span class="styleguide__title"><?php echo $section->getTitle(); ?></span>
        <span class="styleguide__filename"><?php echo $section->getFilename(); ?></span>
    </h3>

    <div class="styleguide__description">
        <p><?php echo nl2br($section->getDescription()); ?></p>
        <?php
            if (count($section->getModifiers()) > 0) {
        ?>
            <ul class="styleguide__modifiers">
                <?php foreach ($section->getModifiers() as $modifier) { ?>
                    <li>
                        <span class="styleguide__modifier-name <?php echo ($modifier->isExtender()) ? 'styleguide__modifier-name--extender' : ''; ?>">
                            <?php echo $modifier->getName(); ?>
                        </span>
                            <?php if ($modifier->isExtender()) { ?>
                                @extend
                                <span class="styleguide__modifier-name"><?php echo $modifier->getExtendedClass(); ?></span>
                            <?php } ?>
                        <?php if ($modifier->getDescription()) { ?>
                            - <?php echo $modifier->getDescription(); ?>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
    </div>

    <div class="styleguide__elements">
        <div class="styleguide__element">
            <?php echo $section->getMarkupNormal(); ?>
        </div>
        <?php foreach ($section->getModifiers() as $modifier) { ?>
            <div class="styleguide__element styleguide__element--modifier <?php ($modifier->isExtender()) ? 'styleguide__element--extender' : ''; ?>">
                <span class="styleguide__element__modifier-label <?php echo ($modifier->isExtender()) ? 'styleguide__element__modifier-label--extender' : ''; ?>"><?php echo $modifier->getName(); ?></span>
                <?php echo $modifier->getExampleHtml(); ?>
            </div>
        <?php } ?>
    </div>

    <div class="styleguide__html">
        <pre class="styleguide__code"><code><?php echo htmlentities($section->getMarkupNormal('{class}')); ?></code></pre>
    </div>
</div>
