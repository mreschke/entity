<?php namespace Mreschke\Repository;

use TestCase;
use Mockery as m;

class DbSelectTest extends TestCase
{
    public function init()
    {
        $this->fake = $this->app->make('Mreschke\Repository\Fake')->store('database');
    }

    public function testSelectFind()
    {
        $client = $this->fake->client->select('id', 'name')->find(1);
        $this->assertInstanceOf('stdClass', $client);
        $this->assertSame((array) $client, [
            "id" => "1",
            "name" => "Mreschke Toyota"
        ]);
        #dd($client);
    }

    public function testSelectAll()
    {
        $clients = $this->fake->client->select('id', 'name', 'disabled')->all();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(count($clients), 4);
        $this->assertInstanceOf('stdClass', $clients[1]);
        $this->assertSame((array) $clients[1], [
            "id" => "1",
            "name" => "Mreschke Toyota",
            "disabled" => false,
        ]);
        #dd($clients);
    }

    public function testSelectSubentity()
    {
        $clients = $this->fake->client->select('address.address', 'address.city')->with('address')->all();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(count($clients), 4);
        $this->assertInstanceOf('stdClass', $clients[1]);
        $this->assertInstanceOf('stdClass', $clients[1]->address);
        $this->assertSame((array) $clients[1]->address, [
            "address" => "100 Mreschke Toyota Lane",
            "city" => "DallasToyota",
        ]);
        #dd((array) $clients[1]->address);
    }

    public function testSelectEntitySubentity()
    {
        $clients = $this->fake->client->select('id', 'note', 'name', 'address.city', 'guid', 'address.address', 'disabled', 'address.note')->with('address')->get();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(count($clients), 4);
        $this->assertInstanceOf('stdClass', $clients[1]);
        $this->assertInstanceOf('stdClass', $clients[1]->address);
        $client = (array) $clients[1];
        $client['address'] = (array) $clients[1]->address;
        $this->assertSame($client, [
            "id" => "1",
            "note" => "Note one",
            "name" => "Mreschke Toyota",
            "address" => [
                "city" => "DallasToyota",
                "address" => "100 Mreschke Toyota Lane",
                "note" => "Address One Note Cool"
            ],
            "guid" => "100XB1C9-55E9-2EE2-9682-84101782417A",
            "disabled" => false,
        ]);
        #dd($client);
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
