In Cobalt Engine, data normalization and validation are handled within the same class.

First, we need to define a data schema:

# Schema Format
Let's consider a simple data submission from our client:
```json
{
    "name":  {
        "first": "JOHN ",
        "last":  "Doe"
    },
    "phone": "(555) 555-1212",
    "email": "JOHN.D@DEVOPS.COM",
    "occupation": "dev",
    "bio": " I sell *software* and **software accessories**."
}
```

How would we go about normalizing all this information, validating it, and inserting it into our database?

Easy: we define a schema.

```php
[
    'name.first' => [
        'set' => 'normalizeName'
    ],
    'name.last' => [
        'set' => 'normalizeName'
    ],
    'phone' => [
        ''
    ]
]
```