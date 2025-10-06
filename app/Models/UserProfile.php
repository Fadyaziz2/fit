<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'age',
        'height',
        'height_unit',
        'weight',
        'weight_unit',
        'address',
        'notes',
        'specialist_id',
        'free_booking_used_at',
    ];

    protected $casts = [
        'user_id'   => 'integer',
        'specialist_id' => 'integer',
        'free_booking_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function specialist()
    {
        return $this->belongsTo(Specialist::class);
    }

    public function getBmiAttribute()
    {
        $height = $this->height;
        $height_unit = $this->height_unit;
        $weight = $this->weight;
        $weight_unit = $this->weight_unit;

        if( $height || $height_unit || $weight || $weight_unit ) 
        {
            // Convert weight to kilograms if unit is not 'kg'
            if ($weight_unit !== 'kg') {
                if ($weight_unit === 'lbs') {
                    $weight = $weight * 0.453592; // Convert pounds to kilograms
                } else {
                    return  null;
                }
            }

            // Convert height to meters based on unit
            switch ($height_unit) {
                case 'cm':
                    $height = $height / 100; // Convert centimeters to meters
                    break;
                case 'feet':
                    $height = $height * 0.3048; // Convert feet to meters
                    break;
                case 'in':
                    $height = $height * 0.0254; // Convert inches to meters
                    break;
                default:
                    return null;
            }

            // Calculate BMI
            $bmi = $weight / ($height * $height);
            return number_format( (float) $bmi, 2,'.','');
        }
    }

    public function getBmrAttribute()
    {
        $male_constant = 5;
        $female_constant = 161;
        $height_constant = 6.25;
        $weight_constant = 10;

        $age = $this->age;
        $gender = optional($this->user)->gender;
        $height = $this->height;
        $weight = $this->weight;
        $height_unit = $this->height_unit;
        $weight_unit = $this->weight_unit;

        // Convert height to cm if necessary
        switch ($height_unit) {
            case 'cm':
                $height_cm = $height;
                break;
            case 'feet':
                $height_cm = $height * 30.48;
                break;
            default:
                $height_cm = 0;
                return null;
        }
        $bmr = ( $weight_constant * $weight ) + ( $height_constant * $height_cm );
        // Calculate BMR based on gender
        if( $gender == 'male' )
        {
            $bmr = $bmr - (5 * $age) + $male_constant;
        } elseif ( $gender == 'female' || $gender == 'other' ) {
            
            $bmr =  $bmr - (5 * $age) - $female_constant;
        } else {
            return null; // "Invalid gender. Please specify 'male' or 'female'.";
        }
        return number_format( (float) $bmr, 2,'.','');
    }

    public function getIdealWeightAttribute()
    {
        $height = $this->height;
        $weight = $this->weight;
        $height_unit = $this->height_unit;
        $weight_unit = $this->weight_unit;
        $gender = optional($this->user)->gender;

        $height_inches = 0;
        // Convert height to inches
        switch ($height_unit) {
            case 'cm':
                $height_inches = $height / 2.54;
                break;
            case 'feet':
                $height_inches = $height * 12;
                break;
            default:
                return null;
        }
        // return $height_inches;
        $base_weight = $gender == 'male' ? 52 : 49; // Base weight in kg

        $weight_per_inch = $gender == 'male' ? 1.9 : 1.7; // Additional weight per inch in kg

        $ideal_weight = $base_weight + ( $weight_per_inch * ( $height_inches - 60));
        
        return number_format( (float) $ideal_weight, 2,'.','');
    }
}
