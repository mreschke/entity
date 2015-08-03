# mreschke/repository

Repository and Entity Manager


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
