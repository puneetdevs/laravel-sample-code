<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $company_id company id
@property varchar $inspection_quote_type inspection quote type
@property decimal $price_pull_stations price pull stations
@property decimal $price_smoke_detectors price smoke detectors
@property decimal $price_alarm_bells price alarm bells
@property decimal $price_heat_detectors price heat detectors
@property decimal $price_smoke_alarms price smoke alarms
@property decimal $price_strobe_lights price strobe lights
@property decimal $price_suite_alarms price suite alarms
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
@property decimal $price_panels price panels
@property decimal $price_annunciators price annunciators
@property decimal $price_suites price suites
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class InspectionQuoteType extends Model 
{
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'inspection_quote_types';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
'inspection_quote_type',
'price_pull_stations',
'price_smoke_detectors',
'price_alarm_bells',
'price_heat_detectors',
'price_smoke_alarms',
'price_strobe_lights',
'price_suite_alarms',
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
'price_panels',
'price_annunciators',
'price_suites'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}