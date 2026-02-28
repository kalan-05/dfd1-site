<?php

namespace App\Filament\Resources\PageBlockResource\Pages;

use App\Filament\Resources\PageBlockResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePageBlocks extends ManageRecords
{
    protected static string $resource = PageBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}