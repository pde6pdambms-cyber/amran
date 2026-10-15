<?php
namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ErpGlCoa
 * 
 * @property character varying $coa_cd
 * @property character varying|null $comp_cd
 * @property character varying|null $coa_tp_cd
 * @property character varying|null $coa_nm
 * @property character varying|null $curr_default
 * @property character varying|null $coa_root
 * @property character varying $coa_sub_st
 * @property character varying $revaluation_st
 * @property character varying $cost_allocation
 * @property character varying $tax_st
 * @property character varying|null $created_by
 * @property character varying|null $updated_by
 * @property timestamp without time zone|null $created_at
 * @property timestamp without time zone|null $updated_at
 * 
 * @property ComCompany $com_company
 * @property ErpGlCoaType $gl_coa_type
 * @property Collection|ErpGlCoaBudget[] $gl_coa_budgets
 * @property Collection|ErpGlCoaBeginBalance[] $gl_coa_begin_balances
 *
 * @package App\Models
 */
class ErpGlCoa extends Model
{
	protected $table 		= 'erp.gl_coa';
	protected $primaryKey 	= 'coa_cd';
	public $incrementing 	= false;
	public $keyType 		= 'string';

	/* protected $casts = [
		'coa_cd' 			=> 'character varying',
		'comp_cd' 			=> 'character varying',
		'coa_tp_cd' 		=> 'character varying',
		'coa_nm' 			=> 'character varying',
		'curr_default' 		=> 'character varying',
		'coa_root' 			=> 'character varying',
		'coa_sub_st' 		=> 'character varying',
		'revaluation_st' 	=> 'character varying',
		'cost_allocation' 	=> 'character varying',
		'tax_st' 			=> 'character varying',
		'coa_group' 		=> 'character varying',
		'created_by' 		=> 'character varying',
		'updated_by' 		=> 'character varying',
		'created_at' 		=> 'timestamp without time zone',
		'updated_at' 		=> 'timestamp without time zone'
	]; */

	protected $fillable = [
		'coa_cd',
		'comp_cd',
		'coa_tp_cd',
		'coa_nm',
		'curr_default',
		'coa_root',
		'coa_sub_st',
		'revaluation_st',
		'cost_allocation',
		'tax_st',
		'coa_group',
		'created_by',
		'updated_by'
	];

	public function com_company()
	{
		return $this->belongsTo(PublicComCompany::class, 'comp_cd');
	}

	public function com_currency()
	{
		return $this->belongsTo(PublicComCurrency::class, 'curr_default' ,'currency_cd');
	}

	public function gl_coa_root()
	{
		return $this->belongsTo(ErpGlCoa::class, 'coa_root', 'coa_cd');
	}

	public function gl_coa_type()
	{
		return $this->belongsTo(ErpGlCoaType::class, 'coa_tp_cd');
	}

	public function gl_coa_budgets()
	{
		return $this->hasMany(ErpGlCoaBudget::class, 'coa_cd');
	}

	public function gl_coa_begin_balances()
	{
		return $this->hasMany(ErpGlCoaBeginBalance::class, 'coa_cd');
	}

	/**
	 * Get the coa_group_nm that owns the ErpGlCoa
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function coa_group_nm()
	{
		return $this->belongsTo(PublicComCode::class, 'coa_group', 'com_cd');
	}

	public static function getId($root = NULL){
		if ($root) {
			$data = ErpGlCoa::where('coa_root',$root)->max(DB::Raw("right(coa_cd,2)::int"));
			return $root.'.'.str_pad($data + 1 , 2 , "0" ,STR_PAD_LEFT);
		}else{
			$data = ErpGlCoa::max(DB::Raw("right(coa_cd,2)::int"));
			return str_pad($data + 1 , 2 , "0" ,STR_PAD_LEFT);
		}
    }
}
