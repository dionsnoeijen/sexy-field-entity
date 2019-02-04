<?php
$arrayOfHierarchies = explode(' ', $hierarchy);
$positionToLook = array_search($sectionName, $arrayOfHierarchies) + 1;
$hasParentConfig = count($arrayOfHierarchies) > $positionToLook;
echo '
public function get' . "$methodName" . '(): ?string
{' . "\n";
if ($hasParentConfig) {
    echo
        'if (empty($this->' . "$propertyName" . ')) {
        return $this->get' . "$arrayOfHierarchies[$positionToLook]" . '()->get' . "$methodName" . '();
    }';
    }
echo '
    return $this->' . "$propertyName" . ';
    }

    public function set' . "$methodName($nullable" . 'string $' . "$propertyName" . '): {{ section }}
    {
        $this->' . "$propertyName" . ' = $' . "$propertyName" . ';
        return $this;
    }';
