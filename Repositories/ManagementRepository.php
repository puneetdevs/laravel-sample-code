<?php

namespace App\Repositories;


use DB;
use App\Exceptions\Handler;
use App\Repositories\BaseRepository;
use App\Models\ManagementContact;
use App\Models\Management;
use App\Models\PlacesManagement;
use DateTime;
use Auth;
use Carbon\Carbon;
/**
 * Class ManagementRepository.
 */
class ManagementRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Management::class;
    }

    
    /**
     * get Management Contact
     *
     * @param  mixed $field
     * @param  mixed $management_id
     *
     * @return void
     */
    public function getManagementContact($field,$id)
    {
        return ManagementContact::where($field, $id );
    }

    /**
     * get Management Place
     *
     * @param  mixed $field
     * @param  mixed $id
     *
     * @return void
     */
    public function getManagementPlace($field,$id)
    {
        return PlacesManagement::where($field, $id );
    }
}
