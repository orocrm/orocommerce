<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
 * Entity that represents tax
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_tax')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_tax_index',
    routeView: 'oro_tax_view',
    routeUpdate: 'oro_tax_update',
    defaultValues: ['security' => ['type' => 'ACL', 'group_name' => '']]
)]
class Tax implements DatesAwareInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['order' => 100, 'identity' => true]]
    )]
    protected ?string $code = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['order' => 200]])]
    protected ?string $description = null;

    /**
     * @var int
     */
    #[ORM\Column(type: 'percent')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['order' => 300]])]
    protected $rate;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.created_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.updated_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * @var bool
     */
    protected $updatedAtSet;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return int
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param int $rate
     * @return $this
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->code;
    }

    /**
     * @return \DateTime
     */
    #[\Override]
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime|null $createdAt
     * @return $this
     */
    #[\Override]
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    #[\Override]
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime|null $updatedAt
     *
     * @return $this
     */
    #[\Override]
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAtSet = false;
        if ($updatedAt !== null) {
            $this->updatedAtSet = true;
        }

        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function isUpdatedAtSet()
    {
        return $this->updatedAtSet;
    }
}
