<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thelia\Api\Resource;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Propel\Runtime\Map\TableMap;
use Symfony\Component\Serializer\Annotation\Groups;
use Thelia\Api\Bridge\Propel\Attribute\Relation;
use Thelia\Api\Bridge\Propel\Filter\OrderFilter;
use Thelia\Api\Bridge\Propel\Filter\SearchFilter;
use Thelia\Api\State\Processor\ProductAssociationProcessor;
use Thelia\Model\Map\AccessoryTableMap;

/**
 * A relation between two products, told apart from the others by its type.
 *
 * `product` is the sheet the relation is read from and `associatedProduct` the one
 * it points at. Both are the `product` table, so the Propel relations are named
 * after their columns rather than after the table, hence the relation aliases:
 * without them the eager loading extension finds no `useProductQuery()` on
 * AccessoryQuery and skips the join in silence.
 *
 * Filtering on `product.id` and `type.code` and ordering on `position` is what
 * lets a theme read one block of a product sheet in a single call.
 *
 * Writes go through ProductAssociationProcessor, which calls the product facade:
 * the events fire, the reciprocal row of a reciprocal type is written, and a
 * product related to itself is refused. Persisting straight to Propel would do
 * none of it.
 *
 * `position` is read-only here. Reordering a block already has its own event and
 * its own back-office screen, and nothing asks the API for it yet.
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/admin/product_associations',
            processor: ProductAssociationProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/admin/product_associations',
        ),
        new Get(
            uriTemplate: '/admin/product_associations/{id}',
            normalizationContext: ['groups' => [self::GROUP_ADMIN_READ, self::GROUP_ADMIN_READ_SINGLE]],
        ),
        new Delete(
            uriTemplate: '/admin/product_associations/{id}',
            processor: ProductAssociationProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_ADMIN_READ]],
    denormalizationContext: ['groups' => [self::GROUP_ADMIN_WRITE]],
)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/front/product_associations',
        ),
        new Get(
            uriTemplate: '/front/product_associations/{id}',
            normalizationContext: ['groups' => [self::GROUP_FRONT_READ, self::GROUP_FRONT_READ_SINGLE]],
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_FRONT_READ]],
)]
#[ApiFilter(
    filterClass: OrderFilter::class,
    properties: [
        'position',
    ],
)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'id',
        'product.id',
        'associatedProduct.id',
        'type.code',
    ],
)]
class ProductAssociation implements PropelResourceInterface
{
    use PropelResourceTrait;

    public const GROUP_ADMIN_READ = 'admin:product_association:read';
    public const GROUP_ADMIN_READ_SINGLE = 'admin:product_association:read:single';
    public const GROUP_ADMIN_WRITE = 'admin:product_association:write';
    public const GROUP_FRONT_READ = 'front:product_association:read';
    public const GROUP_FRONT_READ_SINGLE = 'front:product_association:read:single';

    #[Groups([self::GROUP_ADMIN_READ, self::GROUP_FRONT_READ])]
    public ?int $id = null;

    #[Relation(targetResource: Product::class, relationAlias: 'ProductRelatedByProductId')]
    #[Groups([self::GROUP_ADMIN_READ, self::GROUP_FRONT_READ, self::GROUP_ADMIN_WRITE])]
    public Product $product;

    #[Relation(targetResource: Product::class, relationAlias: 'ProductRelatedByAccessory')]
    #[Groups([self::GROUP_ADMIN_READ, self::GROUP_FRONT_READ, self::GROUP_ADMIN_WRITE])]
    public Product $associatedProduct;

    #[Relation(targetResource: ProductAssociationType::class, relationAlias: 'ProductAssociationType')]
    #[Groups([self::GROUP_ADMIN_READ, self::GROUP_FRONT_READ, self::GROUP_ADMIN_WRITE])]
    public ProductAssociationType $type;

    #[Groups([self::GROUP_ADMIN_READ, self::GROUP_FRONT_READ])]
    public ?int $position = null;

    #[Groups([self::GROUP_ADMIN_READ, self::GROUP_FRONT_READ])]
    public ?\DateTime $createdAt = null;

    #[Groups([self::GROUP_ADMIN_READ, self::GROUP_FRONT_READ])]
    public ?\DateTime $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getAssociatedProduct(): Product
    {
        return $this->associatedProduct;
    }

    public function setAssociatedProduct(Product $associatedProduct): self
    {
        $this->associatedProduct = $associatedProduct;

        return $this;
    }

    public function getType(): ProductAssociationType
    {
        return $this->type;
    }

    public function setType(ProductAssociationType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public static function getPropelRelatedTableMap(): ?TableMap
    {
        return new AccessoryTableMap();
    }
}
