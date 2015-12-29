# mreschke/repository

Repository and Entity Manager


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

	$dispatcher->listen('dynatron.vfi.*.overflow', 'Dynatron\Vfi\Listeners\RepositoryEventSubscription@overflowHandler');


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
