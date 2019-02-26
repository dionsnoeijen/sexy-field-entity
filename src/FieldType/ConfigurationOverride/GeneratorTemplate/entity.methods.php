public function get<?php echo $methodName; ?>(): ?<?php echo $returnType; ?>

{
<?php if ($hasParentConfig) { ?>
    if (empty($this-><?php echo $propertyName; ?>)) {
    try {
    return $this->get<?php echo $hierarchy[$positionToLook]; ?>()->get<?php echo $methodName; ?>();
    } catch (\Throwable $exception) {
    return null;
    }
    };
<?php } ?>
<?php if ($returnType === 'array') { ?>
    $unserialized = $this-><?php echo $propertyName; ?> !== null ? unserialize($this-><?php echo $propertyName; ?>): null;
    return (!empty($unserialized) && is_array($unserialized)) ? array_values($unserialized) : null;
<?php } ?>
<?php if ($returnType === 'string') { ?>
    return $this-><?php echo $propertyName; ?>;
<?php } ?>
}

public function set<?php echo $methodName; ?>(<?php echo $nullable; ?><?php echo $returnType; ?> $<?php echo $propertyName; ?>): {{ section }}
{
<?php if ($returnType === 'array') { ?>
    $this-><?php echo $propertyName; ?> = empty($<?php echo $propertyName; ?>) ? null : serialize($<?php echo $propertyName; ?>);
<?php } ?>
<?php if ($returnType === 'string') { ?>
    $this-><?php echo $propertyName; ?> = $<?php echo $propertyName; ?>;
<?php } ?>

return $this;
}
