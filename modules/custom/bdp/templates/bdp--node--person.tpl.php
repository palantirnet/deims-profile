<?php
/**
 * This template does not have a surrounding XML element because it is re-used
 * by other elements.
 */
?>
<?php print render($content['field_name']); ?>
<?php if (!empty($content['field_organization'])): ?>
<organizationName>
  <?php print render($content['field_organization']); ?>
</organizationName>
<?php endif; ?>
<?php print render($content['field_address']); ?>
<?php print render($content['field_phone']); ?>
<?php print render($content['field_fax']); ?>
<?php print render($content['field_email']); ?>
<?php print render($content['field_url']); ?>
