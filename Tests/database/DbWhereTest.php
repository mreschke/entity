<?php namespace Mreschke\Repository;

use TestCase;
use Mockery as m;

class DbWhereTest extends TestCase
{
    public function init()
    {
        $this->fake = $this->app->make('Mreschke\Repository\Fake')->store('database');
    }

    public function testWhereNoResult()
    {
        $client = $this->fake->client->where('id', 9999)->get();
        $this->assertNull($client);
    }

    public function testWhereInvalidColumn()
    {
        $client = $this->fake->client->where('not valid', 9)->get();
        $this->assertNull($client);
    }

    public function testWhere()
    {
        // Still a collection becuase not ->first()
        $client = $this->fake->client->where('guid', '100XB1C9-55E9-2EE2-9682-84101782417A')->get();
        $this->assertInstanceOf('Illuminate\Support\Collection', $client);
        $this->assertEquals(count($client), 1);
        $this->assertSame((array) $client[1], $this->client1Stub());
        #dd($client);
    }

    public function testWhereFirst()
    {
        $client = $this->fake->client->where('name', 'Mreschke Toyota')->first();
        $this->assertInstanceOf('Mreschke\Repository\Fake\Client', $client);
        $this->assertSame((array) $client, $this->client1Stub());
        #dd($client);
    }

    public function testWhereAnd()
    {
        $clients = $this->fake->client->where('disabled', false)->where('addressID', 1)->get();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(count($clients), 1);
        $this->assertSame((array) $clients[1], $this->client1Stub());
        #dd($clients);
    }

    public function testWhereNull()
    {
        $clients = $this->fake->client->where('note', 'null', true)->showDisabled()->get();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(count($clients), 2);
        $this->assertEquals(array_keys($clients->toArray()), [6, 3]);
        #dd($clients);
    }

    public function testWhereNotNullAnd()
    {
        $clients = $this->fake->client->where('note', 'null', false)->where('id', 1)->get();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(count($clients), 1);
        $this->assertSame((array) $clients[1], $this->client1Stub());
        #dd($clients);
    }

    public function testWhereIn()
    {
        $clients = $this->fake->client->where('note', 'in', ['Note one', 'Note two'])->get();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(count($clients), 2);
        $this->assertSame((array) $clients[1], $this->client1Stub());
    }

    public function testWhereInAnd()
    {
        $clients = $this->fake->client->where('note', 'in', ['Note one', 'Note two'])->where('id', 1)->get();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(count($clients), 1);
        $this->assertSame((array) $clients[1], $this->client1Stub());
        #dd($clients);
    }

    public function testWhereLike()
    {
        $clients = $this->fake->client->where('note', 'like', '%stuff%')->showDisabled()->get();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(count($clients), 2);
        $this->assertEquals(array_keys($clients->toArray()), [5, 4]);
        #dd($clients);
    }

    public function testWhereSubentity()
    {
        $clients = $this->fake->client->where('address.city', 'DallasToyota')->with('address')->get();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(count($clients), 1);
        $this->assertInstanceOf('Mreschke\Repository\Fake\Address', $clients[1]->address);
        $this->assertSame((array) $clients[1]->address, $this->client1AddressStub());
        #dd((array) $clients[1]);
    }

    public function testWhereEntityAndSubentity()
    {
        $clients = $this->fake->client->where('note', 'null', false)->where('address.zip', 75061)->with('address')->get();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertInstanceOf('Mreschke\Repository\Fake\Address', $clients[1]->address);
        $this->assertSame((array) $clients[1]->address, $this->client1AddressStub());
    }

    public function client1Stub()
    {
        return [
            "id" => "1",
            "guid" => "100XB1C9-55E9-2EE2-9682-84101782417A",
            "name" => "Mreschke Toyota",
            "addressID" => "1",
            "note" => "Note one",
            "disabled" => false,
            "repository" => "Mreschke\Repository\Fake\Stores\Db\ClientStore",
        ];
    }

    public function client1AddressStub()
    {
        return [
            "id" => "1",
            "address" => "100 Mreschke Toyota Lane",
            "city" => "DallasToyota",
            "state" => "TX",
            "zip" => "75061",
            "note" => "Address One Note Cool",
            "repository" => "Mreschke\Repository\Fake\Stores\Db\AddressStore",
        ];
    }

    public function tearDown()
    {
        m::close();
    }
    public function setUp()
    {
        parent::setUp();
        $this->init();
    }
}
