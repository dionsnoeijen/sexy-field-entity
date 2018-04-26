public function get{{ methodName }}(): {{ nullable }}int
{
<?php if (!$nullable) { ?>
    if (is_null($this->{{ propertyName }})) {
        throw new \UnexpectedValueException("Property {{ propertyName }} can not be null");
    }
<?php } ?>
    return $this->{{ propertyName }};
}

public function set{{ methodName }}({{ nullable }}int ${{ propertyName }}): {{ section }}
{
    $this->{{ propertyName }} = ${{ propertyName }};
    return $this;
}
