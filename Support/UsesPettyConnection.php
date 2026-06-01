<?php

namespace App\Modules\PettyCash\Support;

trait UsesPettyConnection
{
    public function getConnectionName()
    {
        return PettyDatabase::connectionName();
    }
}

