<?php
namespace App\Models;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
/**
   @property int $company_id company id
@property varchar $number number
@property int $place_id place id
@property int $management_id management id
@property int $inspection_type_id inspection type id
@property int $inspection_quote_type_id inspection quote type id
@property date $date date
@property date $due_date due date
@property date $flush_due_date flush due date
@property int $panel_id panel id
@property decimal $extra_labour extra labour
@property longtext $backflow_notes backflow notes
@property longtext $notes notes
@property smallint $suite_count suite count
@property decimal $price_suite price suite
@property decimal $price_travel price travel
@property decimal $price_panel price panel
@property decimal $price_annunciator price annunciator
@property longtext $other1_description other1 description
@property decimal $price_other1 price other1
@property decimal $sub_devices sub devices
@property decimal $sub_sprinklers sub sprinklers
@property decimal $sub_extinguishers sub extinguishers
@property decimal $sub_lighting sub lighting
@property decimal $sub_suites sub suites
@property decimal $sub_misc sub misc
@property decimal $sub_total sub total
@property decimal $total total
@property smallint $pull_stations pull stations
@property smallint $smoke_detectors smoke detectors
@property smallint $alarm_bells alarm bells
@property smallint $heat_detectors heat detectors
@property smallint $smoke_alarms smoke alarms
@property smallint $strobe_lights strobe lights
@property smallint $suite_alarms suite alarms
@property smallint $duct_smokes duct smokes
@property smallint $general_alarms general alarms
@property smallint $flow_switches flow switches
@property smallint $tamper_switches tamper switches
@property smallint $pressure_switches pressure switches
@property smallint $low_air_switches low air switches
@property smallint $speakers speakers
@property smallint $handsets handsets
@property smallint $fan_controls fan controls
@property smallint $dampers dampers
@property smallint $light_packs light packs
@property smallint $light_heads light heads
@property smallint $extinguishers extinguishers
@property smallint $fire_hoses fire hoses
@property smallint $dry_valves dry valves
@property smallint $wet_valves wet valves
@property smallint $standpipes standpipes
@property smallint $water_gongs water gongs
@property smallint $kitchens kitchens
@property smallint $backflows backflows
@property smallint $hydrants hydrants
@property smallint $other other
@property smallint $fire_pumps fire pumps
@property smallint $ulc_tests ulc tests
@property smallint $suite_heat_detectors suite heat detectors
@property smallint $suite_smoke_alarms suite smoke alarms
@property smallint $suite_strobe_lights suite strobe lights
@property smallint $suite_suite_alarms suite suite alarms
@property smallint $suite_speakers suite speakers
@property decimal $price_pull_stations price pull stations
@property decimal $price_smoke_detectors price smoke detectors
@property decimal $price_alarm_bells price alarm bells
@property decimal $price_heat_detectors price heat detectors
@property decimal $price_smoke_alarms price smoke alarms
@property decimal $price_strobe_lights price strobe lights
@property decimal $price_duct_smokes price duct smokes
@property decimal $price_general_alarms price general alarms
@property decimal $price_flow_switches price flow switches
@property decimal $price_tamper_switches price tamper switches
@property decimal $price_pressure_switches price pressure switches
@property decimal $price_low_air_switches price low air switches
@property decimal $price_speakers price speakers
@property decimal $price_handsets price handsets
@property decimal $price_fan_controls price fan controls
@property decimal $price_dampers price dampers
@property decimal $price_light_packs price light packs
@property decimal $price_light_heads price light heads
@property decimal $price_extinguishers price extinguishers
@property decimal $price_fire_hoses price fire hoses
@property decimal $price_dry_valves price dry valves
@property decimal $price_wet_valves price wet valves
@property decimal $price_standpipes price standpipes
@property decimal $price_water_gongs price water gongs
@property decimal $price_kitchens price kitchens
@property decimal $price_backflows price backflows
@property decimal $price_hydrants price hydrants
@property decimal $price_other price other
@property decimal $price_fire_pumps price fire pumps
@property decimal $price_ulc_tests price ulc tests
@property decimal $price_suite_alarms price suite alarms
@property longtext $comments comments
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class InspectionQuote extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'inspection_quotes';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'number',
        'place_id',
        'management_id',
        'inspection_type_id',
        'inspection_quote_type_id',
        'date',
        'due_date',
        'flush_due_date',
        'panel_id',
        'extra_labour',
        'backflow_notes',
        'notes',
        'suite_count',
        'price_suite',
        'price_travel',
        'price_panel',
        'price_annunciator',
        'other1_description',
        'price_other1',
        'sub_devices',
        'sub_sprinklers',
        'sub_extinguishers',
        'sub_lighting',
        'sub_suites',
        'sub_misc',
        'sub_total',
        'total',
        'pull_stations',
        'smoke_detectors',
        'alarm_bells',
        'heat_detectors',
        'smoke_alarms',
        'strobe_lights',
        'suite_alarms',
        'duct_smokes',
        'general_alarms',
        'flow_switches',
        'tamper_switches',
        'pressure_switches',
        'low_air_switches',
        'speakers',
        'handsets',
        'fan_controls',
        'dampers',
        'light_packs',
        'light_heads',
        'extinguishers',
        'fire_hoses',
        'dry_valves',
        'wet_valves',
        'standpipes',
        'water_gongs',
        'kitchens',
        'backflows',
        'hydrants',
        'other',
        'fire_pumps',
        'ulc_tests',
        'suite_heat_detectors',
        'suite_smoke_alarms',
        'suite_strobe_lights',
        'suite_suite_alarms',
        'suite_speakers',
        'price_pull_stations',
        'price_smoke_detectors',
        'price_alarm_bells',
        'price_heat_detectors',
        'price_smoke_alarms',
        'price_strobe_lights',
        'price_duct_smokes',
        'price_general_alarms',
        'price_flow_switches',
        'price_tamper_switches',
        'price_pressure_switches',
        'price_low_air_switches',
        'price_speakers',
        'price_handsets',
        'price_fan_controls',
        'price_dampers',
        'price_light_packs',
        'price_light_heads',
        'price_extinguishers',
        'price_fire_hoses',
        'price_dry_valves',
        'price_wet_valves',
        'price_standpipes',
        'price_water_gongs',
        'price_kitchens',
        'price_backflows',
        'price_hydrants',
        'price_other',
        'price_fire_pumps',
        'price_ulc_tests',
        'price_suite_alarms',
        'comments'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[
        'date',
        'due_date',
        'flush_due_date'
    ];

    //inspection quote type relation with Inspection Quote
    public function inspectionquotetype(){
        return $this->hasOne(InspectionQuoteType::class,'id','inspection_quote_type_id');
    } 

    //inspection type with Inspection Quote
    public function inspectiontype(){
        return $this->hasOne(InspectionType::class,'id','inspection_type_id');
    } 

    //Place relation with Inspection Quote
    public function place(){
        return $this->hasOne(Place::class,'id','place_id');
    }

    //Place Management relation with Inspection Quote
    public function management(){
        return $this->hasOne(PlacesManagement::class,'id','management_id');
    }

}
