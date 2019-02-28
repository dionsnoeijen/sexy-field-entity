public function get<?php echo $methodName; ?>(): ?<?php echo $returnType . PHP_EOL; ?>
{
    <?php if ($multiple) { ?>
        $unserialized = $this-><?php echo $propertyName; ?> !== null ? unserialize($this-><?php echo $propertyName; ?>): null;
        return array_values($unserialized);
    <?php } else { ?>
        return $this-><?php echo $propertyName; ?>;
    <?php } ?>
}

public function set<?php echo $methodName; ?>(<?php echo $nullable; ?><?php echo $returnType; ?> $<?php echo $propertyName; ?>): {{ section }}
{
    <?php if ($multiple) { ?>
        $this-><?php echo $propertyName; ?> = empty($<?php echo $propertyName; ?>) ? null : serialize($<?php echo $propertyName; ?>);
    <?php } else { ?>
        $this-><?php echo $propertyName; ?> = $<?php echo $propertyName; ?>;
    <?php } ?>
    return $this;
}
