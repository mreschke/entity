# mreschke/repository

- [Introduction](#introduction)
- [Basic Usage and Syntax](#usage)
- [Getting Started](#started)
- [Events](#events)
- [Testing](#testing)
- [Why Was this Written](#why)

<a name="introduction"></a>
# Introduction
A Laravel based active records style entity mapper repository system for all your backends.

*The main purpose of this library is to:*

* Provide beautiful plain-old-php dumps of your entities.  Ever dump an eloquent model?  Ugly and HUGE.
* Provide entity column mapping (map `some_ugly_old_column` in your database to `->perfectColumn` in PHP)
* Provide full eloquent style query builder but based on `->where('perfectColumn')` not `some_ugly_old_column` like eloquent...entity mapping everywhere!
* Provide relationships and `->with()` capability with single lazy loading or bulk eager loading capabilities even across repositories and datacenters.
* Provide active records `->save()` and `->delete()` syntax but yet still on top of beautifully dumped plain-old-php objects (wait what!?!..yeah IoC based singleton caching man)
* Provide ability to swap repository backends on the fly via `$myApp->connection('otherBackend')`
* Provide repo level formatting and constraints like uppercase, lowercase, ucwords, trim, required, default value along with datatype size overflow detection.
* Provide versioned entity mapping.  Ever use https://github.com/thephpleague/fractal as JSON API transformers for your model?  By nature, this entity mapper already does that transformation, but even allows you to version your entity maps for this very reason!  Already mapped it once, why map it again in your REST API?
* Allow cross repository JOINS, even if repositories are on a different server or even a different network/datacenter.
* Provide a fully capable attributes system.  Ever want to store additional columns or metadata about a row, but don't want to add another column?  Every entity row can have as many additional attributes as you want.  You can even `->whereAttribute()` to find entities based on attributes!
* Provide a fully capable object storage system.  Similar to attributes (arbitrary values stored on an entity), but capable of massive size (files, notes, json blobs).  Attributes are for small key/value items, objects are for everything else.



<a name="usage"></a>
# Basic Usage and Syntax

In my examples, I have an application called VFI which has users, dealers, ros, items.  This imaginary entity will be accessed via `$this->vfi` for these examples.


## Getting Records

*NOTE:* `->all()` and `->get()` are identical and can be used interchangeably

```php
<?php
// Single record based on primary key
$user = $this->vfi->user->find(1234);

// Single record based on where statement
$user = $this->vfi->user->where('email', 'mail@mreschke.com')->first();

// Multi record and wheres
$users = $this->vfi->user->all(); //or ->get() also works
$users = $this->vfi->user->where('disabled', false)->get();
$users = $this->vfi->user->where('state', 'TX')->where('disabled', false)->get();

// Selects and pluck
$users = $this->vfi->user->select('id', 'name')->where('disabled', false)->all();
$users = $this->vfi->user->select('id', 'name')->pluck('name', 'id');

// Ordering records
$users = $this->vfi->user->where('disabled', false)->orderBy('name')->all();

// Counting records at a db level (not result level)
$users = $this->vfi->user->count();
$users = $this->vfi->user->where('disabled', false)->count();

// Limiting records
$users = $this->vfi->user->limit(0, 10)->get();
```

## Joins
```php
<?php

```


## Relationships

Relationships involve subentities.  That is, entities that relate to other entities.  This repo system can access subentities in a few ways.  One is with a `->join` feature.  The `->join` feature cannot join cross repo and you must always `->select('specific', 'columns')` as * is not supported.  Another is the `->with` feature which is like an application level join and can be used both in `lazy` and `eager` modes. The `->with` feature is beneficial because you can join cross repository.

To access relationships (subentities) you can use the `->with()` keyword or use a custom build entity method.  Using the `->with()` keyword in the proper place along the chain will cause either a lazy load or an eager load.  Lazy loading will run one relationship for each eneity and can be VERY inefficient if handled improperly.  Eager loading allows you to pre load all relationships for all entities queried.  Eager loading will actually run 2 queries.  One for your main entites, and one for your relationships based on an IN statement.  These results are then combined in PHP.

You can also `->select()` with subentities using the period, ex: `->select('id', 'name', 'address.state')` where address is the subentity.

```php
<?php
// Lazy loaded using ->with() method
$user = $this->vfi->user->find(1234)->with('address'); //lazy query happens here
echo $user->address->state;

// Lazy loaded using automatic method
$user = $this->vfi->user->find(1234);
echo $user->address->state; //lazy query happens here

// Lazy loaded in a loop...careful, this is where it gets inefficient as its one query per entity
$users = $this->vfi->user->all();
foreach ($users as $user) {
    echo $user->address->state; //lazy query happens here
}

// Eager loaded using ->with().  Far more efficient if you need it on multiple objects.
// Notice ->with() is BEFORE ->all() or ->get()
$users = $this->vfi->user->with('address')->all();

// Complex ->with() based query
$query = $this->vfi->client->with('address');
$totalRecords = $query->count(false); //count total before a filter
#$query->select('id', 'name', 'address.city, 'address.state'); // this works, so does select * which is default
$query->where('disabled', false);
$query->where('address.state', 'CO');
$query->limit(0, 10);
$results = $query->get();

// Complex ->join() based query
$query = $this->vfi->client->joinAddress();
$totalRecords = $query->count(false); //count total before a filter
$query->select('id', 'name', 'address.city, 'address.state'); //required for join


```

## Attributes System

You can store additional attributes (or metadata) about any entity in the system.  This is off by default for all entities.  See [Getting Started](#started) for how to setup this up.

Attributes can be both lazy loaded and eager loaded.  Be very careful here as using lazy loading improperly is very inefficient.

*NOTE:* Attributes are meant to be small key/values, not large million line strings. When you load any one attribute, ALL attributes for that entity are also loaded into the same `->attributes` property.  So having hundreds of small attributes per ONE entity is also not advised. The `->object()` system is however designed for unlimited and massive any size objects.

```php
<?php
// Lazy loaded attributes using ->with() method
$user = $this->vfi->user->find(1234)->with('attributes'); //lazy query happens here
echo $user->attributes('some-attribute');

// Lazy loaded attributes using automatic method
$user = $this->vfi->user->find(1234);
echo $user->attributes('some-attribute'); //lazy query happens here

// Lazy loaded in a loop...careful, this is where it gets inefficient as its one query per entity
$users = $this->vfi->user->all();
foreach ($users as $user) {
    echo $user->attributes('some-attribute'); //lazy query happens here
}

// Eager loaded using ->with().  Far more efficient if you need it on multiple objects.
// Notice ->with() is BEFORE ->all() or ->get()
$users = $this->vfi->user->with('attributes')->all();

// Find entity records based on an attribute value
$users = $this->vfi->user->whereAttribute('some-attribute', 'someValue')->get();

// Find entity records that even have this attribute (value is not important)
$users = $this->vfi->user->whereAttribute('some-attribute')->get();

// Setting attributes
$this->vfi->user->find(1234)->attributes('new-attribute', 'new value here');

// Delete attribute (single)
$this->vfi->user->find(1234)->forgetAttribute('some-attribute');

// Delete all attributes
// There is no way to delete all attributes other than looping them all manually
$user = $this->vfi->user->find(1234)->with('attributes');
foreach ($user->attributes as $attribute) {
    $user->forgetAttribute($attribute)
}
```


## Entity Formatting

Before you insert new entities into the database you can run your array through the entity formatter.  This will format each colum based on your store map section.  This allows you to uppercase, lowercase, trim...the values before being inserted.  If a value exceeds a defined `size` property, an `overflow` event is fired, see [Events](#events) for details.

```php
<?php
$newEntities = 'this is an array of your items you want to insert';
foreach ($newEntities as $entity) {
    // Format entities first
    $entity->format();

    // Save to backend
    $entity->save();
}
```

## Deleting Records

```php
<?php
// Single entity delete
$this->vfi->roItem->find(1234)->delete();
$this->vfi->roItem->where('roNum', 222258)->first()->delete();

// Bulk query builder based delete (most efficient)
// Results in query: DELETE FROM table WHERE techNum = 842
$this->vfi->roItem->where('techNum', 903)->delete();

// Multiple collection of entities
// Results in query: DELETE FROM table WHERE IN (1,2,3,...)
$ros = $this->vfi->roItem->where('techNum', 903)->get();
$this->vfi->roItem->delete($ros);

// Multiple array of entities
// Results in query: DELETE FROM table WHERE IN (1,2,3,...)
$ros = $this->vfi->roItem->where('techNum', 903)->get();
$ros = $ros->toArray();
$this->vfi->roItem->delete($ros);

// Multiple array of arrays
// Results in query: DELETE FROM table WHERE IN (1,2,3,...)
$ros = $this->vfi->roItem->where('techNum', 903)->get();
$tmp = [];
foreach ($ros as $ro) {
    $tmp[] = (array) $ro;
}
$this->vfi->roItem->delete($ros);

// Multiple array of arrays manually
// Results in query: DELETE FROM table WHERE IN (1,2,3,...)
$this->vfi->roItem->delete([
    ['id' => 1],
    ['id' => 2],
    ['id' => 3]
]);

// Trying to delete * should fail in case we messed up the query builder
// To delete * use ->truncate instead
$this->vfi->roItem->delete();
```


## Updating Records

```php
<?php
// Update single entity using ->save
$client = $this->vfi->client->find(5975);
$client->name = "New Name";
$client->save();

// Update single entity using ->update and passing back the object
$client = $this->vfi->client->find(5975);
$client->name = "New Name";
$this->vfi->client->update($client);

// Update same column(s) on bulk records based on ->where
// This is a query builder level update and the most efficient!
$clients = $this->vfi->client
    ->where('disabled', true)
    ->update(['name' => 'DISABLED', 'date' => date()]);

```



<a name="started"></a>
# Getting Started

This section explains how to setup `mreschke/repository`.  How you can use this system for your own entities.






<a name="events"></a>
# Events

The repository fires many events.  All are string based events, not class based.
They are string based so they can be separated by entity, much like how eloquent
fires events by model name so you can listen to individual models not just all models.

All events are prefixed with `repository.yourrepo.yourentity`.

Example dynatron/vfi events on a `customer` entity.

**Entity level events:**

* `repository.dynatron.vfi.customer.overflow`
* `repository.dynatron.vfi.customer.attributes.saving`
* `repository.dynatron.vfi.customer.attributes.saved`
* `repository.dynatron.vfi.customer.attributes.deleting`
* `repository.dynatron.vfi.customer.attributes.deleted`
* `repository.dynatron.vfi.customer.attributes.saving`

**Store level events:**

* `repository.dynatron.vfi.customer.saving`
* `repository.dynatron.vfi.customer.saved`
* `repository.dynatron.vfi.customer.creating`
* `repository.dynatron.vfi.customer.created`
* `repository.dynatron.vfi.customer.updating`
* `repository.dynatron.vfi.customer.updated`
* `repository.dynatron.vfi.customer.deleting`
* `repository.dynatron.vfi.customer.deleted`
* `repository.dynatron.vfi.customer.truncating`
* `repository.dynatron.vfi.customer.truncated`

**Listeners**

Laravel can listen to wildcard events:

```php
<?php
$dispatcher->listen('repository.dynatron.vfi.*.overflow',
  'Dynatron\Vfi\Listeners\RepositoryEventSubscription@overflowHandler');
```


<a name="testing"></a>
# Testing

This is an mrcore module, so mrcore5 is required to run the tests.

I know, never test against an actual database.  Well I don't know how to do that
yet.  My tests are more integration tests rather than unit tests.  I use
a test.sqlite database and a localhost mongo installation will be required.  I
automatically append the proper database connection information at run-time so
no need to adjust config/database.php.  The sqlite database is excluded from git
and mongo is cheap, you can delete the fake*-repository database if needed.

Seed once...test anytime

    Fake/Database/create
    ./test





<a name="why"></a>
# Why Was this Written

*fixme* not complete


Back in 2015 repositories were all the range in the Laravel community.  Their benefits were obvious but their implementation was verbose, cumbersome and not as easy to "query build" like eloquent.

Most people like the idea of having a repository layer just in case they ever decide to swap out the backend.  The problem for most people is that engineering for the future of backend changes is often a waste of time and considered over engineering.  This wasn't true for my case as I was currently in the middle of re-writing all of my software from MSSQL into MySQL and MongoDB.  This was a 5 year chess game and required some apps to run with legacy MSSQL backend while others would run with new MySQL backends.   These backend were temporary and moving and migrating monthly during this transition phase. This meant I was forced to think "API first".  In other words, I didn't need to care where my data currently was and where it was going to end up.  All I needed to think about was how I wanted to interact with my data in an ideal world.  The repository pattern allowed me to build beautiful APIs into my data even though the backend was MSSQL garbage.  Instead of legacy columns like `users_first_name` I could map that to simply `$user->name`, something eloquent does not provide.  


So if your database looks like this

    Table: tblContacts
    ---------------
    contact_id
    first_name
    last_name
    email

    Table: tblAddresses
    -------------------
    address_id
    address
    zip_code

You can build perfectly mapped (translated) entities that look like this

    // We don't want to use the word contacts or tblContacts, we want to use 'users'
    // And we don't want first_name, we want firstName...thus the entity mapper
    var_dump( $this->vfi->user->find(1)->with('address') )
    Mreschke\Vfi\User {
        id: 1
        firstName: "Matthew"
        lastName: "Reschke"
        email: "mail@mreschke.com"
        address: {
            id: "3212"
            address: "Some address"
            zip: 75067
        }
    }

These now perfeclty mapped entities also act like plain old PHP objects.  Meaning you can var_dump() or dd() or dump() them in PHP and get very nice looking results, instead of like Eloquent or Query builder where you get a million other Laravel properites along with it.  *This gives you clean dumps, which are a huge benifit!*

Not only are our entities now perfectly and consistently mapped for output, we can also reliably query on those perfectly mapped columns too!

```php
<?php
// So we can now use firstName not first_name everywhere, like so
$user = $this->vfi->user->where('firstName', 'Matthew')->first()
```

Of course, since your entities are just plan old PHP objects, you can add any other methods or properties you choose as helpers.  Like if you wanted a `byName` helper, just add it to your entity




# Dev Notes

## HTTP Store

If I build an HTTP JSON store, what would the URL's look like for full query builder usage?

Since this is public HTTP API, I need to always know context, like WHO is the logged in user
that is querying the API.  Becuase if they call users/179 I need to know the calling $user 
actually has access to user 179 etc...  In PHP based library this is not a problem
becuase I am the once calling the API.  But if HTTP, then anyone can call it

all()
http://iam/user
http://iam/user/byUser(179)
http://iam/user/managersByDealer(5975)

where()
http://iam/user/where('disabled',true)
http://iam/user/where('disabled',true)/where('id','>',100)

order()
http://iam/user/orderBy('id')
http://iam/user/where('disabled',true)/orderBy('id')

find()
http://iam/user/179
http://iam/client/byExtract(4345)
http://iam/client/whereHostname('bgmo')/orderBy('id')

Methods
http://iam/user/179/types
http://iam/user/179/apps
http://iam/user/179/hasPerm('admin')
http://iam/user/179/isEmployee
http://iam/client/accessibleBy(179)

Relationships
http://iam/client/179/with('host','address')
http://iam/client/179/host
http://iam/client/179/address


