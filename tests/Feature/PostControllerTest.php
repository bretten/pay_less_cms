<?php

namespace Tests\Feature;

use Tests\TestCase;

class PostControllerTest extends TestCase
{
    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/posts');

        $response->assertStatus(200);
    }

    /**
     * Test create method is not implemented
     *
     * @return void
     */
    public function testCreateIsNotImplemented()
    {
        $response = $this->get('/posts/create');

        $response->assertStatus(501);
    }

    /**
     * Test store method is not implemented
     *
     * @return void
     */
    public function testStoreIsNotImplemented()
    {
        $response = $this->get('/posts/store');

        $response->assertStatus(501);
    }

    /**
     * Test show method is not implemented
     *
     * @return void
     */
    public function testShowIsNotImplemented()
    {
        $response = $this->get('/posts/show');

        $response->assertStatus(501);
    }

    /**
     * Test edit method is not implemented
     *
     * @return void
     */
    public function testEditIsNotImplemented()
    {
        $response = $this->get('/posts/edit');

        $response->assertStatus(501);
    }

    /**
     * Test update method is not implemented
     *
     * @return void
     */
    public function testUpdateIsNotImplemented()
    {
        $response = $this->get('/posts/update');

        $response->assertStatus(501);
    }

    /**
     * Test destroy method is not implemented
     *
     * @return void
     */
    public function testDestroyIsNotImplemented()
    {
        $response = $this->get('/posts/destroy');

        $response->assertStatus(501);
    }
}
