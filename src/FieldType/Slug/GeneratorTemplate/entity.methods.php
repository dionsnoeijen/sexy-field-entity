public function get{{ methodName }}(): Tardigrades\SectionField\ValueObject\Slug
{
    if (is_null($this->{{ propertyName }})) {
        throw new \UnexpectedValueException("Property {{ propertyName }} can not be null");
    }
    return Tardigrades\SectionField\ValueObject\Slug::fromString($this->{{ propertyName }});
}
