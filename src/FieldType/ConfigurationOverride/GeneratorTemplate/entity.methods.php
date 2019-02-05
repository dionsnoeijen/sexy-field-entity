public function get<?php echo $methodName; ?>(): ?<?php echo $returnType; ?>

{
<?php if ($hasParentConfig) { ?>
    if (empty($this-><?php echo $propertyName; ?>)) {
    return $this->get<?php echo $hierarchy[$positionToLook]; ?>()->get<?php echo $methodName; ?>();
    };
<?php } ?>
<?php if ($returnType === 'array') { ?>
    return unserialize($this-><?php echo $propertyName; ?>);
<?php } ?>
<?php if ($returnType === 'string') { ?>
    return $this-><?php echo $propertyName; ?>;
<?php } ?>
}

public function set<?php echo $methodName; ?>(<?php echo $nullable; ?>string $<?php echo $propertyName; ?>): {{ section }}
{
$this-><?php echo $propertyName; ?> = $<?php echo $propertyName; ?>;
return $this;
}
