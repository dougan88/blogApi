# blogApi

For developer environment was used homestead (https://laravel.com/docs/5.6/homestead).
More changes and documentation are on the way.

Installation:


```
git clone https://github.com/dougan88/blogApi blog
cd blog
composer install
```



Modify your database credentials in .env file. Ex.:
```
DATABASE_URL=mysql://homestead:secret@127.0.0.1:3306/blog
```

Create database:
```
./bin/console doctrine:database:create
```
Run migrations:
```
./bin/console doctrine:migrations:migrate
```

#### Creating a new post:
```
POST: http://blog.test/posts
```

```json
{
  "title":"testTitle", 
  "body": "test body", 
  "tag":
    [
      "sport", 
      "science"
    ]
}
```
---------------

#### Response:

```json
{
  "success": true,
  "error": null,
  "result": {
    "id": 9
  }
}
```
---------------
#### Retrieving all posts with filters:
```
GET: http://blog.test/posts/all/[published posts only: 1/0]/[sort order: ASC/DESC - 1/0]/[tag name]
     http://blog.test/posts/all/0/0/science
```
---------------

#### Response:

```json
{
    "success": true,
    "error": null,
    "result": 
    {
      "posts": [
        {
          "title": "testTitle",
          "body": "test body",
          "publication_date": 
          {
            "date": "2018-08-09 17:49:46.000000",
            "timezone_type": 3,
            "timezone": "UTC"
          },
          "published": false
        }
      ],
    }
}
```

#### Updating a post:
```
PUT: http://symfony.test/posts/[post id]
     http://symfony.test/posts/9
```

```json
{
  "title":"Updated title", 
  "body": "Updated body",
  "published": "true"
}
```
---------------

#### Response:

```json
{
    "success":true,
    "error":null,
    "result": {
        "posts": {
            "title":"Updated title",
            "body":"Updated body",
            "publication_date": {
                "date":"2018-08-09 17:49:46.000000",
                "timezone_type":3,
                "timezone":"UTC"
            },
            "published":true
        }
    }
}
```

#### Deleting a post:
```
DELETE: http://symfony.test/posts/[post id]
        http://symfony.test/posts/9
```
---------------

#### Response:

```json
{
    "success": true,
    "error": null,
    "result": "Post was deleted"
}
```
