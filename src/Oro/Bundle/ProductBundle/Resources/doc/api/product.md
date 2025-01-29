# Oro\Bundle\ProductBundle\Entity\Product

## ACTIONS

### get

Retrieve a specific product record.

{@inheritdoc}

### get_list

Retrieve a collection of product records.

{@inheritdoc}

### create

Create a new product record.

The created record is returned in the response.

{@inheritdoc}

#### 1. Static options for product attributes

| Attribute| Options | Description |
|----------|---------|-------------|
| decrementQuantity | 1 / 0 | Yes / No |
| manageInventory | 1 / 0 | On order submission in the default checkout flow / No (custom) |
| backOrder | 1 / 0 | Yes / No |
| productType | "simple" / "configurable" | |
| status | "enabled" / "disabled" |  |
| featured | true / false |  |
| newArrival | true / false |  |
| | | |

#### 2. Creating related entities together with the Product entity:

When creating a Product entity there are certain relations or associations with other entities
which require by default that you specify their type and id so that they are loaded.

But if you need to create a new entity from a relation, you have the option to do so, but you must
use the **included** section. See documentation about this section [here](https://doc.oroinc.com/api/create-update-related-resources/)

For example, if we look at the manageInventory field, in the **data** section

```JSON
      "manageInventory": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "1abcd"
        }
      }
```

we have a temporary ID (recommended to be a GUID, as the documentation says) "1abcd" which is
used to identify the data used for creating this entity, data taken from the **included** section :

```JSON
    {
      "type": "entityfieldfallbackvalues",
      "id": "1abcd",
      "attributes": {
        "fallback": "systemConfig",
        "scalarValue": null,
        "arrayValue": null
      }
    }
```

The same applies to other relation entities like "localizedfallbackvalues" type, which are used for some properties
like meta fields. You can see in the detailed example for product creation.

#### 3. Using ProductPrecisionUnits:

A ProductPrecisionUnit is a relation between the product and unit of quantity and other details like
conversion rates. Also, there is the concept of the primary ProductPrecisionUnit which is actually the
default precision unit, and it is mandatory.

There are a few restrictions and situations that the API caller should know:

- when sending the **primaryUnitPrecision** you need to specify the unit code, but it is mandatory that
this unit code is found between the items of the **unitPrecisions**

#### 4. Specify Category

The Category is not directly handled by the Product, but you can specify it when creating or updating
a Product entity, in the **data** section. Example:

```JSON
      "category": {
        "data": {
          "type": "categories",
          "id": "4"
        }
      }
```

#### 5. Creating configurable products

When creating a product, there are three types available: simple, kit, and configurable. Configurable products must
have custom product attributes in the product attribute family specified and a result product variants can be added to a
configurable product. A product variant is a simple product attached to a parent configurable product.

To create a configurable product, the product type must be specified in the **attributes** section of the product.

Example:

```JSON
      "attributes": {
        "sku": "test-api-1"
        ...
        "productType": "configurable",
        "variantFields": ["custom-variant-field"],
        ...
      }
```

The variantFields values must correspond to a custom product attribute within the product attribute family specified in the
**relationships** section. Example:

```JSON
      "attributeFamily": {
        "data": {
          "type": "attributefamilies",
          "id": "1"
        }
      },
```

The above examples are valid if "custom-variant-field" is present in the "attributeFamily" , and the configurable product
will be created. If the "variantFields" is empty , the configurable product will be created but the variant fields will not be active.

When creating a simple product with a product attribute family that has a configurable attribute, the value of this attribute can be set
in the **relationships** section. Example:

```JSON
      "relationships": {
       ...
        "customvariantfield": {
           "data": {
             "type": "productcustomvariantfield",
             "id": "custom-variant-field-id"
           }
        }
      ....
      }
```

The simple product with custom attribute can now be linked to a configurable product as a product variant.

#### 6. Specify variants (for configurable products only)

When adding a new configurable product you can specify the variants of that product. To be able to specify
variants of a product first you have to add a configurable attribute for product entity and create the simple products
that will be the variants of the configurable product. After these steps you can specify variants for a new configurable 
product. Example:

```JSON
      "variantLinks": {
        "data": [
          {
            "type": "productvariantlinks",
            "id": "variant-link1"
          },
          {
            "type": "productvariantlinks",
            "id": "variant-link2"
          }
        ]
      }
```

and in the included section we specify the variants:

```JSON
    {
      "type": "productvariantlinks",
      "id": "variant-link1",
      "attributes": {
        "visible": true
      },
      "relationships": {
        "parentProduct": {
          "data": {
            "type": "products",
            "id": "1"
          }
        },
        "product": {
          "data": {
            "type": "products",
            "id": "65"
          }
        }
      }
    },
    {
      "type": "productvariantlinks",
      "id": "variant-link2",
      "attributes": {
        "visible": true
      },
      "relationships": {
        "parentProduct": {
          "data": {
            "type": "products",
            "id": "1"
          }
        },
        "product": {
          "data": {
            "type": "products",
            "id": "67"
          }
        }
      }
    }
```

For **parentProduct** id you need to specify any id of an existing product from the system,
the link between the configurable product that is added on this request and the variants will be handled internally
by the API. In **product** tag we specify the ID of the product that will be a variant of the created product.

#### 7. Creating product kits

To create a product kit the "type" must be specified in the **attributes** section of the product.

Example:

```JSON
      "attributes": {
        "sku": "PKSKU1"
        ...
        "productType": "kit",
        ...
      }
```

Add images definition in the **data** section. Example:
Also, a product kit must have at least one kit item in the **data** section. Example:

Example:

```JSON
      "data": {
          ...
          "relationships": {
              ...
              "kitItems": {
                  "data": [
                      {
                          "type": "productkititems",
                          "id": "productkititem-1"
                      }
                  ]
              }
          }
      }
```

And the corresponding kit item with kit item label and kit item product in the **included** section. Example:

```JSON
    "included": [
        ...
        {
            "type": "productkititems",
            "id":"productkititem-1",
            "attributes": {
                "optional": false
            },
            "relationships": {
                "productKit": {
                    "data": {
                        "type": "products",
                        "id": "42"
                    }
                },
                "labels": {
                    "data": [
                        {
                            "type": "productkititemlabels",
                            "id": "productkititemlabel-1"
                        }
                    ]
                },
                "kitItemProducts": {
                    "data": [
                        {
                            "type": "productkititemproducts",
                            "id": "productkititemproduct-1"
                        }
                    ]
                },
                "productUnit": {
                    "data": {
                        "type": "productunits",
                        "id": "item"
                    }
                }
            }
        },
        {
            "type": "productkititemlabels",
            "id": "productkititemlabel-1",
            "attributes": {
                "fallback": null,
                "string": "Product Kit Item 1 Label"
            },
            "relationships": {
                "productKitItem": {
                    "data": {
                        "type": "productkititems",
                        "id": "productkititem-1"
                    }
                }
            }
        },
        {
            "type": "productkititemproducts",
            "id": "productkititemproduct-1",
            "attributes":{
                "sortOrder": "1"
            },
            "relationships": {
                "kitItem": {
                    "data": {
                        "type": "productkititems",
                        "id": "productkititem-1"
                    }
                },
                "product": {
                    "data": {
                        "type": "products",
                        "id": "4242"
                    }
                }
            }
        }
    ]
```

#### 8. Using product images

Add images definition in the **data** section. Example:

```JSON
    "images": {
      "data": [
        {
          "type": "productimages",
          "id": "product-image-1"
        }
      ]
    }
```

In the **included** section. Example:

```JSON
    {
      "type": "files",
      "id": "file-1",
      "attributes": {
        "mimeType": "image/jpeg",
        "originalFilename": "onedot.jpg",
        "fileSize": 631,
        "content": "/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAIBAQIBAQICAgICAgICAwUDAwMDAwYEBAMFBwYHBwcGBwcICQsJCAgKCAcHCg0KCgsMDAwMBwkODw0MDgsMDAz/2wBDAQICAgMDAwYDAwYMCAcIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAz/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD+f+iiigD/2Q=="
      }
    },
    {
      "type": "productimagetypes",
      "id": "product-image-type-1",
      "attributes": {
        "productImageTypeType": "main"
      },
      "relationships": {
        "productImage": {
          "data": {
            "type": "productimages",
            "id": "product-image-1"
          }
        }
      }
    },
    {
      "type": "productimagetypes",
      "id": "product-image-type-2",
      "attributes": {
        "productImageTypeType": "listing"
      },
      "relationships": {
        "productImage": {
          "data": {
            "type": "productimages",
            "id": "product-image-1"
          }
        }
      }
    },
    {
      "type": "productimages",
      "id": "product-image-1",
      "relationships": {
        "image": {
          "data": {
            "type": "files",
            "id": "file-1"
          }
        },
        "types": {
          "data": [
            {
              "type": "productimagetypes",
              "id": "product-image-type-1"
            },
            {
              "type": "productimagetypes",
              "id": "product-image-type-2"
            }
          ]
        },
        "product": {
          "data": {
            "type": "products",
            "id": "product-id"
          }
        }
      }
    }
```

The example above also creates product image mandatory subresources : files and types.
The type attribute of the product image type model ("productImageTypeType") should be a valid type
of image defined in themes and it is not directly handled by the API.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "attributes": {
      "sku": "test-api-1",
      "status": "enabled",
      "variantFields": [],
      "productType": "simple",
      "featured": true,
      "newArrival": false,
      "availability_date": "2018-01-01"
    },
    "relationships": {
      "owner": {
        "data": {
          "type": "businessunits",
          "id": "1"
        }
      },
      "organization": {
        "data": {
          "type": "organizations",
          "id": "1"
        }
      },
      "names": {
        "data": [
          {
            "type": "productnames",
            "id": "names-1"
          },
          {
            "type": "productnames",
            "id": "names-2"
          }
        ]
      },
      "shortDescriptions": {
        "data": [
          {
            "type": "productshortdescriptions",
            "id": "short-descriptions-1"
          },
          {
            "type": "productshortdescriptions",
            "id": "short-descriptions-2"
          }
        ]
      },
      "descriptions": {
        "data": [
          {
            "type": "productdescriptions",
            "id": "descriptions-1"
          },
          {
            "type": "productdescriptions",
            "id": "descriptions-2"
          }
        ]
      },
      "slugPrototypes": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "slug-id-1"
          }
        ]
      },
      "attributeFamily": {
        "data": {
          "type": "attributefamilies",
          "id": "1"
        }
      },
      "primaryUnitPrecision": {
        "data": {
            "type": "productunitprecisions",
            "id": "product-unit-precision-id-3"
        }
      },
      "unitPrecisions": {
        "data": [
          {
            "type": "productunitprecisions",
            "id": "product-unit-precision-id-1"
          },
          {
            "type": "productunitprecisions",
            "id": "product-unit-precision-id-2"
          }
        ]
      },
      "productShippingOptions":{
        "data":[
          {
            "type":"productshippingoptions",
            "id":"product-shipping-options-1"
          }
        ]
      },
      "inventory_status": {
        "data": {
          "type": "prodinventorystatuses",
          "id": "out_of_stock"
        }
      },
      "pageTemplate": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "1xyz"
        }
      },
      "manageInventory": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "1abcd"
        }
      },
      "inventoryThreshold": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "2abcd"
        }
      },
      "highlightLowInventory": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "low1abcd"
        }
      },
      "lowInventoryThreshold": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "low2abcd"
        }
      },
      "isUpcoming": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "product-is-upcoming"
        }
      },      
      "minimumQuantityToOrder": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "3abcd"
        }
      },
      "maximumQuantityToOrder": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "4abcd"
        }
      },
      "decrementQuantity": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "5abcd"
        }
      },
      "backOrder": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "6abcd"
        }
      },
      "category": {
        "data": {
          "type": "categories",
          "id": "4"
        }
      }
    }
  },
  "included": [
    {
      "type": "entityfieldfallbackvalues",
      "id": "1xyz",
      "attributes": {
        "fallback": null,
        "scalarValue": "short",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "1abcd",
      "attributes": {
        "fallback": "systemConfig",
        "scalarValue": null,
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "2abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "31",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "low1abcd",
      "attributes": {
        "fallback": "systemConfig",
        "scalarValue": null,
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "low2abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "41",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "product-is-upcoming",
      "attributes": {
        "fallback": null,
        "scalarValue": "1",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "3abcd",
      "attributes": {
        "fallback": "systemConfig",
        "scalarValue": null,
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "4abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "12",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "5abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "1",
        "arrayValue": null
      }
    },
    {
      "type": "entityfieldfallbackvalues",
      "id": "6abcd",
      "attributes": {
        "fallback": null,
        "scalarValue": "0",
        "arrayValue": null
      }
    },
    {
      "type": "productnames",
      "id": "names-1",
      "attributes": {
        "fallback": null,
        "string": "Test product"
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    },
    {
      "type": "productnames",
      "id": "names-2",
      "attributes": {
        "fallback": null,
        "string": "Product in Spanish"
      },
      "relationships": {
        "localization": {
          "data": {
            "type": "localizations",
            "id": "1"
          }
        }
      }
    },
    {
      "type": "productshortdescriptions",
      "id": "short-descriptions-1",
      "attributes": {
        "fallback": null,
        "text": "Test product short description"
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    },
    {
      "type": "productshortdescriptions",
      "id": "short-descriptions-2",
      "attributes": {
        "fallback": null,
        "text": "Product Short Description in Spanish"
      },
      "relationships": {
        "localization": {
          "data": {
            "type": "localizations",
            "id": "1"
          }
        }
      }
    },
    {
      "type": "productdescriptions",
      "id": "descriptions-1",
      "attributes": {
        "fallback": null,
        "wysiwyg": {
          "value": "Test product description",
          "style": null,
          "properties": null
        }
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    },
    {
      "type": "productdescriptions",
      "id": "descriptions-2",
      "attributes": {
        "fallback": null,
        "wysiwyg": {
          "value": "Product Short Description in Spanish",
          "style": null,
          "properties": null
        }
      },
      "relationships": {
        "localization": {
          "data": {
            "type": "localizations",
            "id": "1"
          }
        }
      }
    },
    {
      "type": "localizedfallbackvalues",
      "id": "slug-id-1",
      "attributes": {
        "fallback": null,
        "string": "test-prod-slug",
        "text": null
      },
      "relationships": {
        "localization": {
          "data": null
        }
      }
    },
    {
      "type": "productunitprecisions",
      "id": "product-unit-precision-id-1",
      "attributes": {
          "precision": "0",
          "conversionRate": "5",
          "sell": "1"
      },
      "relationships": {
        "unit": {
          "data": {
            "type": "productunits",
            "id": "each"
          }
        }
      }
    },
    {
      "type": "productunitprecisions",
      "id": "product-unit-precision-id-2",
      "attributes": {
          "precision": "0",
          "conversionRate": "10",
          "sell": "1"
      },
      "relationships": {
        "unit": {
          "data": {
            "type": "productunits",
            "id": "item"
          }
        }
      }
    },
    {
      "type": "productunitprecisions",
      "id": "product-unit-precision-id-3",
      "attributes": {
          "precision": "0",
          "conversionRate": "2",
          "sell": "1"
      },
      "relationships": {
        "unit": {
          "data": {
            "type": "productunits",
            "id": "set"
          }
        }
      }
    },
    {
      "type":"productshippingoptions",
      "id":"product-shipping-options-1",
      "attributes":{
        "weightValue":10,
        "dimensionsLength":0.6,
        "dimensionsWidth":0.8,
        "dimensionsHeight":0.1
      },
      "relationships":{
        "productUnit":{
          "data":{
            "type":"productunits",
            "id":"set"
          }
        },
        "weightUnit":{
          "data":{
            "type":"weightunits",
            "id":"kg"
          }
        },
        "dimensionsUnit":{
          "data":{
            "type":"lengthunits",
            "id":"m"
          }
        }
      }
    }
  ]
}
```
{@/request}

### update

Edit a specific product record.

The updated record is returned in the response.

{@inheritdoc}

#### 1. Using ProductPrecisionUnits:

Besides what it is mentioned in the create Product section above, for the ProductPrecisionUnits
there are a few more restrictions and situations that the API caller should know:

- inside an item of the **unitPrecisions** list, if we have the **id** field, the only mandatory
field remaining mandatory is the "unit_code"
the primary unit precision

- if we send in the list of **unitPrecisions** a new "unit_code" that is not found among the
list of unit precisions on the Product main entity, then it will be created and added to the list

- if in the **primaryUnitPrecision** we send a new "unit_code", other then what it is found on
the current product primary unit precision, then the current primary unit precision will be replaced
with the newly provided one

**Important** - when updating product unit precisions you need to pay attention to:

- specify all unit precisions for the product (including primary), even though you want to update the attributes for
only one of them

- in the **included** section specify the product unit that you want to update but don't forget to use the same id as it has in the
database (id which you use in the **data** section on the field) and specify the **update** property in the **meta** subsection
of the **included** section (see example below)

#### 2. Updating "localizedfallbackvalues" (localized fields) and "entityfieldfallbackvalues" (options with fallbacks) types

**Important** - When you want to update existing related entities, it can only be done by using the
**included** section, the same way it is used in the create section. What is important to mention is:

* you must use the real ID of the related entity that you want to update, example:

> - **data** section

```JSON
        "manageInventory": {
          "data": {
            "type": "entityfieldfallbackvalues",
            "id": "466"
          }
        }
```

> - **included** section

```JSON
        {
          "meta": {
             "update": true
          },
          "type": "localizedfallbackvalues",
          "id": "807",
          "attributes": {
            "fallback": null,
            "string": "Test value - updated",
            "text": null
          }
        }
```

* use the **update** flag to specify it is an update on an existing entity, otherwise it will attempt
  the creation of a new entity of that type

```JSON
          "meta": {
             "update": true
          }
```

**Important** when wanting to update the current entity by modifying a relation, which is actually
a `to-many` relationship with another entity, you must specify all of the entities from that list.
If you don't do that, the system will set on that relation the input that has been received. So for
example if I have the "names" relation which holds a collection of "localizedfallbackvalues" type,
with 8 entities, and I specify only 2 of these entities in the input, then in the database I will
have only those 2 saved and all the other (6 entities) will be removed. Example for updating the
"names" relation with modifying the text for a specific localization:

> - in the **data** section:

```JSON
      "names": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "807"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "810"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "814"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "812"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "813"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "811"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "808"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "815"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "816"
          },
          {
            "type": "localizedfallbackvalues",
            "id": "817"
          }
        ]
```

> - in the **included** section:

```JSON
    {
      "meta": {
         "update": true
      },
      "type": "localizedfallbackvalues",
      "id": "807",
      "attributes": {
        "fallback": null,
        "string": "Test value - updated",
        "text": null
      }
    }
```

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "id": "67",
    "attributes": {
      "sku": "test-api-3",
      "status": "enabled",
      "variantFields": [],
      "productType": "simple",
      "featured": true,
      "newArrival": false
    },
    "relationships": {
      "owner": {
        "data": {
          "type": "businessunits",
          "id": "1"
        }
      },
      "organization": {
        "data": {
          "type": "organizations",
          "id": "1"
        }
      },
      "attributeFamily": {
        "data": {
          "type": "attributefamilies",
          "id": "1"
        }
      },
      "inventory_status": {
        "data": {
          "type": "prodinventorystatuses",
          "id": "in_stock"
        }
      },
      "pageTemplate": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "355"
        }
      },
      "manageInventory": {
        "data": {
          "type": "entityfieldfallbackvalues",
          "id": "466"
        }
      },
      "category": {
        "data": {
          "type": "categories",
          "id": "4"
        }
      },
      "names": {
        "data": [
          {
            "type": "productnames",
            "id": "807"
          }
        ]
      },
      "slugPrototypes": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "907"
          }
        ]
      },
      "primaryUnitPrecision": {
        "data": {
            "type": "productunitprecisions",
            "id": "453"
        }
      },
      "unitPrecisions": {
        "data": [
          {
            "type": "productunitprecisions",
            "id": "454"
          },
          {
            "type": "productunitprecisions",
            "id": "455"
          }
        ]
      },
      "productShippingOptions":{
        "data":[
          {
            "type":"productshippingoptions",
            "id":"product-shipping-options-1"
          }
        ]
      }
    }
  },
  "included": [
    {
      "meta": {
         "update": true
      },
      "type": "entityfieldfallbackvalues",
      "id": "466",
      "attributes": {
        "fallback": null,
        "scalarValue": "0",
        "arrayValue": null
      }
    },
    {
      "meta": {
         "update": true
      },
      "type": "localizedfallbackvalues",
      "id": "907",
      "attributes": {
        "fallback": null,
        "string": "test-prod-slug-updated",
        "text": null
      }
    },
    {
      "type": "productunitprecisions",
      "id": "453",
      "attributes": {
          "precision": "7",
          "conversionRate": "5",
          "sell": "0"
      },
      "relationships": {
        "unit": {
          "data": {
            "type": "productunits",
            "id": "set"
          }
        }
      }
    },
    {
      "type": "productshippingoptions",
      "id": "product-shipping-options-1",
      "attributes": {
        "weightValue": 10,
        "dimensionsLength": 0.6,
        "dimensionsWidth": 0.8,
        "dimensionsHeight": 0.1
      },
      "relationships": {
        "productUnit": {
          "data": {
            "type": "productunits",
            "id": "set"
          }
        },
        "weightUnit": {
          "data": {
            "type": "weightunits",
            "id": "kg"
          }
        },
        "dimensionsUnit": {
          "data": {
            "type": "lengthunits",
            "id": "m"
          }
        }
      }
    }
  ]
}
```
{@/request}

### delete

Delete a specific product record.

{@inheritdoc}

### delete_list

Delete a collection of product records.

{@inheritdoc}

## FIELDS

### sku

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### names

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### inventory_status

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### status

#### create

{@inheritdoc}

**Note:**
If a value for this field is not provided, the `disabled` value will be used.

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### productType

#### create

{@inheritdoc}

**Note:**
If a value for this field is not provided, the `simple` value will be used.

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### attributeFamily

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### unitPrecisions

#### create

{@inheritdoc}

**Note:**
This field is required but when it is not passed, the `primaryUnitPrecision` value is used.

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### primaryUnitPrecision

#### create

{@inheritdoc}

**The required field.**

**Note:**
The unit code provided for this field will be automatically added to the **unitPrecisions** list.

#### update

{@inheritdoc}

**Note:**
The unit code provided for this field must exist in the **unitPrecisions** list.

**This field must not be empty, if it is passed.**

### kitItems

#### create

**This field must not be empty, if the product type is "kit".**

{@inheritdoc}

#### update

{@inheritdoc}

**This field must not be empty, if it is passed and the product type is "kit".**

## SUBRESOURCES

### attributeFamily

#### get_subresource

Retrieve the attribute family configured for a specific product.

#### get_relationship

Retrieve an ID of the attribute family that a specific product record belongs to.

#### update_relationship

Replace the attributeFamily for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "attributefamilies",
    "id": "1"
  }
}
```
{@/request}

### backOrder

#### get_subresource

Retrieve the record of the fallback entity value for backOrder for a specific product.

#### get_relationship

Retrieve an ID of the backOrder flag.

#### update_relationship

Replace the backOrder entity fallback value for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "entityfieldfallbackvalues",
    "id": "117"
  }
}
```
{@/request}

### brand

#### get_subresource

Retrieve the brand for a specific product.

#### get_relationship

Retrieve an ID of the brand of the product.

#### update_relationship

Replace the brand for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "brands",
    "id": "1"
  }
}
```
{@/request}

### decrementQuantity

#### get_subresource

Retrieve the record of the fallback entity value for decrementQuantity flag for a specific product.

#### get_relationship

Retrieve an ID of the decrementQuantity flag for a specific product.

#### update_relationship

Replace the decrementQuantity entity fallback value for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "entityfieldfallbackvalues",
    "id": "116"
  }
}
```
{@/request}

### inventoryThreshold

#### get_subresource

Retrieve the record of the fallback entity value for inventoryThreshold for a specific product.

#### get_relationship

Retrieve an ID of the inventoryThreshold for a specific product.

#### update_relationship

Replace the inventoryThreshold entity fallback value for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "entityfieldfallbackvalues",
    "id": "115"
  }
}
```
{@/request}

### isUpcoming

#### get_subresource

Retrieve the service records that store flag if this product will be available later.

#### get_relationship

Retrieve an ID of the isUpcoming fallback entity for a specific product.

#### update_relationship

Replace the isUpcoming entity fallback value for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "entityfieldfallbackvalues",
    "id": "1"
  }
}
```
{@/request}

### lowInventoryThreshold

#### get_subresource

Retrieve the fallback value for lowInventoryThreshold for a specific product.

#### get_relationship

Retrieve an ID of the lowInventoryThreshold for a specific product.

#### update_relationship

Replace the fallback value for lowInventoryThreshold for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "entityfieldfallbackvalues",
    "id": "1"
  }
}
```
{@/request}

### inventory_status

#### get_subresource

Retrieve the record of the fallback entity value for inventory_status flag for a specific product.

#### get_relationship

Retrieve an ID of the inventory_status flag for a specific product.

#### update_relationship

Replace the inventory_status for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "prodinventorystatuses",
    "id": "out_of_stock"
  }
}
```
{@/request}

### manageInventory

#### get_subresource

Retrieve the record of the fallback entity value for manageInventory flag for a specific product.

#### get_relationship

Retrieve an ID of the manageInventory flag for a specific product.

#### update_relationship

Replace the manageInventory entity fallback value for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "entityfieldfallbackvalues",
    "id": "114"
  }
}
```
{@/request}

### highlightLowInventory

#### get_subresource

Retrieve the fallback value for highlightLowInventory flag for a specific product.

#### get_relationship

Retrieve an ID of the highlightLowInventory flag for a specific product.

#### update_relationship

Replace the fallback value for highlightLowInventory for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "entityfieldfallbackvalues",
    "id": "1"
  }
}
```
{@/request}

### maximumQuantityToOrder

#### get_subresource

Retrieve the record of the fallback entity value for maximumQuantityToOrder for a specific product.

#### get_relationship

Retrieve an ID of the maximumQuantityToOrder for a specific product.

#### update_relationship

Replace the maximumQuantityToOrder entity fallback value for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "entityfieldfallbackvalues",
    "id": "113"
  }
}
```
{@/request}

### minimumQuantityToOrder

#### get_subresource

Retrieve the record of the fallback entity value for minimumQuantityToOrder for a specific product.

#### get_relationship

Retrieve an ID of the minimumQuantityToOrder for a specific product.

#### update_relationship

Replace the minimumQuantityToOrder entity fallback value for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "entityfieldfallbackvalues",
    "id": "112"
  }
}
```
{@/request}

### pageTemplate

#### get_subresource

Retrieve the record of the fallback entity value for pageTemplate for a specific product.

#### get_relationship

Retrieve an ID of the pageTemplate value used for a specific product.

#### update_relationship

Replace the pageTemplate entity fallback value for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "entityfieldfallbackvalues",
    "id": "448"
  }
}
```
{@/request}

### owner

#### get_subresource

Retrieve the record of the business unit that is the owner of a specific product record.

#### get_relationship

Retrieve the ID of the business unit that is the owner of a specific product record.

#### update_relationship

Replace the owner of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "businessunits",
    "id": "1"
  }
}
```
{@/request}

### organization

#### get_subresource

Retrieve the record of the organization a specific product record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific product record belongs to.

#### update_relationship

Replace the organization that a specific product belongs to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "organizations",
    "id": "1"
  }
}
```
{@/request}

### names

#### get_subresource

Retrieve the records for the names of a specific product record.

#### get_relationship

Retrieve a list of IDs for the names of a specific product record.

#### add_relationship

Set the names of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productnames",
      "id": "593"
    },
    {
      "type": "productnames",
      "id": "594"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the names for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productnames",
      "id": "593"
    },
    {
      "type": "productnames",
      "id": "594"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the names of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productnames",
      "id": "69"
    },
    {
      "type": "productnames",
      "id": "592"
    }
  ]
}
```
{@/request}

### descriptions

#### get_subresource

Retrieve the records for the descriptions of a specific product record.

#### get_relationship

Retrieve a list of IDs for the descriptions of a specific product record.

#### add_relationship

Set the descriptions of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productdescriptions",
      "id": "608"
    },
    {
      "type": "productdescriptions",
      "id": "609"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the descriptions for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productdescriptions",
      "id": "608"
    },
    {
      "type": "productdescriptions",
      "id": "609"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the descriptions of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productdescriptions",
      "id": "70"
    },
    {
      "type": "productdescriptions",
      "id": "601"
    }
  ]
}
```
{@/request}

### metaDescriptions

#### get_subresource

Retrieve the records for the metaDescriptions of a specific product record.

#### get_relationship

Retrieve a list of IDs for the metaDescriptions of a specific product record.

#### add_relationship

Set the metaDescriptions of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "479"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "638"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the metaDescriptions for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "479"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "638"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the metaDescriptions of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "479"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "638"
    }
  ]
}
```
{@/request}

### metaKeywords

#### get_subresource

Retrieve the records for the metaKeywords of a specific product record.

#### get_relationship

Retrieve a list of IDs for the metaKeywords of a specific product record.

#### add_relationship

Set the metaKeywords of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "480"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "647"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the metaKeywords for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "480"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "647"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the metaKeywords of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "480"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "647"
    }
  ]
}
```
{@/request}

### metaTitles

#### get_subresource

Retrieve the records for the metaTitles of a specific product record.

#### get_relationship

Retrieve a list of IDs for the metaTitles of a specific product record.

#### add_relationship

Set the metaTitles of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "628"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "629"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the metaTitles for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "628"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "629"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the metaTitles of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "628"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "629"
    }
  ]
}
```
{@/request}

### shortDescriptions

#### get_subresource

Retrieve the records for the shortDescriptions of a specific product record.

#### get_relationship

Retrieve a list of IDs for the shortDescriptions of a specific product record.

#### add_relationship

Set short descriptions records for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productshortdescriptions",
      "id": "71"
    },
    {
      "type": "productshortdescriptions",
      "id": "610"
    }
  ]
}
```
{@/request}

Set the shortDescriptions of a specific product record.

#### update_relationship

Replace the shortDescriptions for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productshortdescriptions",
      "id": "71"
    },
    {
      "type": "productshortdescriptions",
      "id": "610"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the shortDescriptions of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productshortdescriptions",
      "id": "71"
    },
    {
      "type": "productshortdescriptions",
      "id": "610"
    }
  ]
}
```
{@/request}

### slugPrototypes

#### get_subresource

Retrieve the records for the slugPrototypes of a specific product record.

#### get_relationship

Retrieve a list of IDs for the slugPrototypes of a specific product record.

#### add_relationship

Set the slugPrototypes of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "72"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "619"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the slugPrototypes for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "72"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "619"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the slugPrototypes of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "72"
    },
    {
      "type": "localizedfallbackvalues",
      "id": "619"
    }
  ]
}
```
{@/request}

### primaryUnitPrecision

#### get_subresource

Retrieve the record for the primary unit precision of a specific product record.

#### get_relationship

Retrieve the ID of the primaryUnitPrecision of a specific product record.

#### update_relationship

Replace the primaryUnitPrecision for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productunitprecisions",
    "id": "1"
  }
}
```
{@/request}

### unitPrecisions

#### get_subresource

Retrieve the records for the unitPrecisions of a specific product record.

#### get_relationship

Retrieve a list of IDs for the unitPrecisions of a specific product record.

#### add_relationship

Set the unitPrecisions of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productunitprecisions",
      "id": "1"
    },
    {
      "type": "productunitprecisions",
      "id": "65"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the unitPrecisions for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productunitprecisions",
      "id": "1"
    },
    {
      "type": "productunitprecisions",
      "id": "65"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the unit precisions of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productunitprecisions",
      "id": "1"
    },
    {
      "type": "productunitprecisions",
      "id": "65"
    }
  ]
}
```
{@/request}

### images

#### get_subresource

Get the related productImages entity for a specific product.

#### get_relationship

Retrieve the ID of productImages for a specific product.

#### add_relationship

Set the productImages of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productimages",
      "id": "1"
    },
    {
      "type": "productimages",
      "id": "2"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the productImages for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productimages",
      "id": "1"
    },
    {
      "type": "productimages",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the productImages of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productimages",
      "id": "1"
    },
    {
      "type": "productimages",
      "id": "2"
    }
  ]
}
```
{@/request}

### variantLinks

#### get_subresource

Retrieve the variant products of a specific product record.

#### get_relationship

Retrieve a list of IDs for the variant products of a specific product record.

#### add_relationship

Set the variant products of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productvariantlinks",
      "id": "1"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the variant products for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productvariantlinks",
      "id": "1"
    },
    {
      "type": "productvariantlinks",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the variant products of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productvariantlinks",
      "id": "1"
    }
  ]
}
```
{@/request}

### defaultVariant

#### get_subresource

Retrieve the default variant for a specific product.

#### get_relationship

Retrieve an ID of the default variant of the product.

#### update_relationship

Replace the default variant for a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "id": "1"
  }
}
```
{@/request}

### kitItems

#### get_subresource

Retrieve the product kit items of a specific product record.

#### get_relationship

Retrieve a list of IDs for the product kit items of a specific product record.

#### add_relationship

Set the product kit items of a specific product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productkititems",
      "id": "productkititem-1"
    }
  ],
  "included": [
    {
      "type": "productkititems",
      "id":"productkititem-1",
      "attributes": {
        "optional": false
      },
      "relationships": {
        "productKit": {
          "data": {
            "type": "products",
            "id": "42"
          }
        },
        "labels": {
          "data": [
            {
              "type": "productkititemlabels",
              "id": "productkititemlabel-1"
            }
          ]
        },
        "kitItemProducts": {
          "data": [
            {
              "type": "productkititemproducts",
              "id": "productkititemproduct-1"
            }
          ]
        },
        "productUnit": {
          "data": {
            "type": "productunits",
            "id": "item"
          }
        }
      }
    },
    {
      "type": "productkititemlabels",
      "id": "productkititemlabel-1",
      "attributes": {
        "fallback": null,
        "string": "Product Kit Item 1 Label"
      },
      "relationships": {
        "productKitItem": {
          "data": {
            "type": "productkititems",
            "id": "productkititem-1"
          }
        }
      }
    },
    {
      "type": "productkititemproducts",
      "id": "productkititemproduct-1",
      "attributes":{
        "sortOrder": "1"
      },
      "relationships": {
        "kitItem": {
          "data": {
            "type": "productkititems",
            "id": "productkititem-1"
          }
        },
        "product": {
          "data": {
            "type": "products",
            "id": "4242"
          }
        }
      }
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the product kit items for a specific product. The product kit items collection cannot be empty.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productkititems",
      "id": "1"
    },
    {
      "type": "productkititems",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the product kit items of a specific product record. The last product kit item cannot be deleted from the product kit.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "productkititems",
      "id": "1"
    }
  ]
}
```
{@/request}

# Extend\Entity\EV_Prod_Inventory_Status

## ACTIONS

### get

Retrieve a specific product inventory status record.

Product inventory status defines an product's availability (**In Stock**, **Out of Stock** and **Discontinued**).

### get_list

Retrieve a collection of product inventory status records.

Product inventory status defines an product's availability (**In Stock**, **Out of Stock** and **Discontinued**).
