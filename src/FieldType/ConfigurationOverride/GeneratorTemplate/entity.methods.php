<?php
$positionToLook = array_search($sectionName, $hierarchy) + 1;
$hasParentConfig = count($hierarchy) > $positionToLook;
echo '
public function get' . "$methodName" . '(): ?string
{' . "\n";
if ($hasParentConfig) {
    echo
        'if (empty($this->' . "$propertyName" . ')) {
        return $this->get' . "$hierarchy[$positionToLook]" . '()->get' . "$methodName" . '();
    }';
}
echo '
    return $this->' . "$propertyName" . ';
    }

    public function set' . "$methodName($nullable" . 'string $' . "$propertyName" . '): {{ section }}
    {
        $this->' . "$propertyName" . ' = $' . "$propertyName" . ';
        return $this;
    }'."\n";
