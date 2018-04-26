public function get{{ methodName }}(): {{ nullable }}float
{
<?php if (!$nullable) { ?>
    if (is_null($this->{{ propertyName }})) {
        throw new \UnexpectedValueException("Property {{ propertyName }} can not be null");
    }
<?php } ?>
    return $this->{{ propertyName }};
}

public function set{{ methodName }}({{ nullable }}float ${{ propertyName }}): {{ section }}
{
    $this->{{ propertyName }} = ${{ propertyName }};
    return $this;
}
