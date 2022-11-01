<?php namespace Mreschke\Repository;

use TestCase;
use Mockery as m;

class DbFindTest extends TestCase
{
    public function init()
    {
        $this->fake = $this->app->make('Mreschke\Repository\Fake')->store('database');
        $this->fake2 = $this->app->make('Mreschke\Repository\Fake2')->store('database');
    }

    public function testFindOne()
    {
        $client = $this->fake->client->find(1);
        $this->assertInstanceOf('Mreschke\Repository\Fake\Client', $client);
        $this->assertSame((array) $client, $this->client1Stub());
        #dd($client);
    }

    public function testFindAll()
    {
        $clients = $this->fake->client->all();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(cnt($clients), 4);
        $this->assertSame((array) $clients[1], $this->client1Stub());
        $this->assertEquals(array_keys($clients->toArray()), [4, 2, 3, 1]); //default orderBy
        #dd($clients);
    }

    public function testFindAllShowDisabled()
    {
        $clients = $this->fake->client()->showDisabled()->all();
        $this->assertInstanceOf('Illuminate\Support\Collection', $clients);
        $this->assertEquals(cnt($clients), 6);
        $this->assertSame((array) $clients[1], $this->client1Stub());
        $this->assertEquals(array_keys($clients->toArray()), [5, 4, 2, 6, 3, 1]); //default orderBy
        #dd($clients);
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



    public function XtestX()
    {
        $manager = m::mock('Mreschke\Repository\Fake');
        $manager = $this->iam;


        // Bypass the manager...get to client store directly
        $storeClassname = 'Mreschke\Repository\Fake\Stores\Db\ClientStore';
        $store = m::mock($storeClassname);
        $client = new Mreschke\Repository\Fake\Client($storeClassname);

        $x = m::mock('Mreschke\Repository\Fake\Client');

        #$store->join('table', 'one', '=', 'two');
    }









    public function xtestInsertSingleRecord()
    {
        $client = $this->iam->client->insert([
            'guid' => '700XB1C9-55E9-2EE2-9682-84101782417A',
            'name' => 'Mreschke Single Insert',
            'address_id' => 7,
            'disabled' => true
        ]);

        dump($client);

        $client->delete();

        #$mockedApp = m::mock('Illuminate\Foundation\Application');
        #$manager = new Mreschke\Repository\Fake\Fake($mockedApp);
        #$manager->
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
