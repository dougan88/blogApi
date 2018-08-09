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

Creating a new post:
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
