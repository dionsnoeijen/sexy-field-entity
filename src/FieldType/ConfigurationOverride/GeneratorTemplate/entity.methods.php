public function get<?php echo $methodName; ?>(): ?string
{
<?php if ($hasParentConfig) { ?>
    if (empty($this-><?php echo $propertyName; ?>)) {
    return $this->get<?php echo $hierarchy[$positionToLook]; ?>()->get<?php echo $methodName; ?>();
    }';
<?php } ?>
return $this-><?php echo $propertyName; ?>;
}

public function set<?php echo $methodName; ?>(<?php echo $nullable; ?>string $<?php echo $propertyName; ?>): {{ section }}
{
$this-><?php echo $propertyName; ?> = $<?php echo $propertyName; ?>;
return $this;
};