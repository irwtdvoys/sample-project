# Sample Project

## Purpose

Standalone PHP app with the following requirements:

+ Console command to download and import UK postcodes
    + http://parlvid.mysociety.org/os/ is my chosen data source
+ Controller action to return postcodes with partial string matches as JSON
+ Controller action to return postcodes near a location (latitude,longitude) as JSON

## Installation

### Dependencies

This project uses Composer to manage dependencies, there are a small number of libraries that will need to be installed for the project to function as expected which can be done as follows on the CLI from the project folder:

```sh
$ composer install
```

### Local Docker Environment

The project has details for running in a local docker-based environment. Three containers are used: nginx, php-fpm and mariaDB. Together they allow API access and the ability to run CLI scripts.

To start the environment on the CLI run the following from within the project folder:

```sh
$ cd docker
$ docker-compose -p sampleproject up
``` 

### Database Setup

A database structure file in included as `scripts/data/schema.sql`. This file can be imported into the database with the following command entering the password `root` when prompted assuming example `.env` config:

```
$ mysql -u root -p --host 127.0.0.1 base_test < schema.sql
```


### Environment Variables

The project makes use of a `.env` file to keep and config which may need to be kept secure out of the repo. There is an example file with very insecure details `.env.example` in the repo, simply renaming it to `.env` will allow the system to function without having to alter anything in the docker config.

## Usage

### Import Script

The import can be run from within the php-fpm docker container. The container can be entered and the script run with the following commands (if the docker environment was brought up as detailed above).

There is a sample csv taken from the full mySociety open data set, in this case the csv containing only postcodes starting `BH`.

```sh
$ docker exec -it sampleproject_php_1 bash
$ ./bin/run.php --job Import --data '{"file":"scripts/data/bh.csv"}'
```

### Controllers

#### Health Check

There is a simple health check endpoint in the app. It can be useful for scenarios where you need to check the status of the API without doing anything to computationally expensive (it just returns a 200). For example AWS ELB poll a health check to detect unavailable instances.

| URL  | Method| Description|
| ------------- | ------------- | ------------- |
| `health/`  | `GET`  | Checks the status of the API  |

#### Postcodes

| URL  | Method| Description|
| ------------- | ------------- | ------------- |
| `postcode/{code}/`  | `GET`  | Returns stored detailed for the postcode specified by `code`  |
| `postcode/search/`  | `POST`  | Allows for searching postcodes by `code` or `location`  |

##### Search Examples

The search endpoint can perform multiple different searches depending on the payload sent to it. Returned results are paginated incase the dataset is too large. By default (without a `Range` header upto the first 25 results will be returned).
                                                                                                 
The system allows for client driven pagination enabling different page sizes for different outputs or use cases. This can be set using the `Range` header as shown in the following examples.

The response will contain a `Content-Range` header giving details on the range returned and the total available. If the full dataset fits into the requested range a 200 is returned, otherwise a 206 will be.

**Code**

Code search takes a string `code` and performs a partial match for any stored postcodes that match. 

*Request*

This request will return the first 20 results matching `BH12`. The data is sent in JSON format so must include the appropriate `Content-Type` header.

```sh
curl -v -X POST \
  http://localhost/postcodes/search/ \
  -H 'Content-Type: application/json' \
  -H 'Range: indices=0-19' \
  -d '{
	"code": "BH12"
}'
```

*Response*

The following headers show the response to the above curl. It shows details of the data range, the format of the data as well as that it is not the full dataset.

```
< HTTP/1.1 206 Partial Content
< Server: nginx/1.9.14
< Date: Wed, 06 Jun 2018 20:05:54 GMT
< Content-Type: application/json; charset=UTF-8
< Transfer-Encoding: chunked
< Connection: keep-alive
< X-Powered-By: PHP/7.2.6
< Content-Range: indices 0-19/1090
```

The body of the response contains data in the following format (full response for the request not shown)
```
[
  {
    "code": "BH12 9JQ",
    "location": {
      "type": "Point",
      "coordinates": [
        -1.913646,
        50.740372
      ]
    }
  }
]
```

**Location**

By searching on location the postcodes can be orders based on distance from a specific point by sending a GeoJSON point object as part of the payload:

**Request**

This call doesn't include a `Range` header so the API will be assuming a range of 0-24.

```sh
curl -v -X POST \
  http://localhost/postcodes/search/ \
  -H 'Content-Type: application/json' \
  -d '{
	"location": {
        "type": "Point",
        "coordinates": [
            -1.9,
            50.9
        ]
    }
}'
```

**Response**

```
< HTTP/1.1 206 Partial Content
< Server: nginx/1.9.14
< Date: Wed, 06 Jun 2018 20:14:52 GMT
< Content-Type: application/json; charset=UTF-8
< Transfer-Encoding: chunked
< Connection: keep-alive
< X-Powered-By: PHP/7.2.6
< Content-Range: indices 0-24/21928
```

For location based searches an additional field is added to the postcodes `distance` the number of meters from the search point.

```
[
    {
        "code": "BH21 5RH",
        "location": {
            "type": "Point",
            "coordinates": [
                -1.894967,
                50.89711
            ]
        },
        "distance": 477
    }
]
```


## Finally

While the chosen MariaDB solution does solve the criteria I did toy around with other data storage solutions. My goto is ElasticSearch for anything geospatial however I felt that without the polygon data for the full regions of a postcode it wouldn't be able to provide additional benefits.

Changing it in the future is still an option. If different data becomes available a new set of ElasticSearch adapters for the models and repositories would enable the data to be stored and searched in an ElasticSearch cluster without any changes to the core code.
