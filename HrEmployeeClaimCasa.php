<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\HrEmployeeClaimCasaCashout;
use App\Models\HrEmployeeFamily;
use App\Models\VwEmployeeMasaKerjaFull;

class HrEmployeeClaimCasa extends Model
{
    protected $table        = 'hrms.hr_employee_claim_casa';
    protected $primaryKey   = 'hr_employee_claim_casa_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hr_employee_claim_casa_id', 'employee_cd', 'casa_tp', 'casa_date', 'claim_st', 'response', 'created_by', 'updated_by',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    static function getAllData()
    {
        $data = HrEmployeeClaimCasa::select(
					'hr_employee_claim_casa.*',
					'employee.employee_nm',
					'casatp.code_nm as casa_tp_nm',
					'claimst.code_nm as claim_st_nm',
					'employee.badge_no as badge_no',
					'employeeOrg.division_cd as division_cd',
					'employeeOrg.employee_tp'
				)
				->leftJoin('hrms.hr_employee as employee', 'employee.employee_cd', 'hr_employee_claim_casa.employee_cd')
				->leftJoin('com_code as claimst', 'claimst.com_cd', 'hr_employee_claim_casa.claim_st')
				->leftJoin('com_code as casatp', 'casatp.com_cd', 'hr_employee_claim_casa.casa_tp')
				->leftJoin('hrms.hr_employee_org as employeeOrg', function($join){
					$status = '1';
					$join->on('employeeOrg.employee_cd', 'hr_employee_claim_casa.employee_cd');
					$join->where('employeeOrg.active_st', $status);
				})
				->orderBy("employeeOrg.employee_tp")
				->orderBy("employee.badge_no")
				->orderBy('casa_date', 'asc');

        return $data;
    }

	static function getDataSaldo()
	{
		$prosesMonth = date('n');

        $casaYearEligible = configuration('CASA_CASHOUT_GIVEN');

		$data = VwEmployeeMasaKerjaFull::select(
					'vw_employee_masa_kerja_full.*',
					'employee.staff_cd',
					'employee.staff_nm',
					'employee.level_tp',
					'employee.level_tp_nm',
					'employee.badge_no',
					'employeeOrg.employee_tp'
				)
				->join('hrms.vw_employee as employee','employee.employee_cd','vw_employee_masa_kerja_full.employee_cd')
				->leftJoin('hrms.hr_employee_org as employeeOrg', function($join){
					$status = '1';
					$join->on('employeeOrg.employee_cd', 'vw_employee_masa_kerja_full.employee_cd');
					$join->where('employeeOrg.active_st',$status);
				})
				->whereRaw("
				--case when employee.region_penerimaan_tp='REGION_PENERIMAAN_TP_01' then
				case when employee.region_penerimaan_tp in ('REGION_PENERIMAAN_TP_01','REGION_PENERIMAAN_TP_04','REGION_PENERIMAAN_TP_05') then
					(vw_employee_masa_kerja_full.in_year>=$casaYearEligible /* and extract(month from first_date_join)=$prosesMonth */)
				else
					(vw_employee_masa_kerja_full.in_year>=1 /* and extract(month from first_date_join)=$prosesMonth */)
				end
				")
				->whereNotIn('employeeOrg.employee_tp',['EMPLOYEE_TP_03'])
				->orderBy("employeeOrg.employee_tp")
				->orderBy("employee.badge_no");

        return $data;
    }

    static function getResumeCasaYear($employeeCd, $campSt, $regionPenerimaan, $dateJoin, $year)
    {
		$remainingCasa = null;
		$pp = 2;

		$employee = HrEmployee::where('employee_cd',$employeeCd)->first();

		$masaKerja = VwEmployeeMasaKerjaFull::where('employee_cd', $employeeCd)->first();
		if ($masaKerja) {
		if ($masaKerja->in_year >= 1) {

			$employeeFamily = HrEmployeeFamily::where
							('employee_cd',$employeeCd)
							->where('dependent_st', '1')
							->get();
			$totalDependent = count($employeeFamily);

			//--Region penerimaan lokal
			//if ($employee->region_penerimaan_tp == 'REGION_PENERIMAAN_TP_01'){
			if (in_array($employee->region_penerimaan_tp, array('REGION_PENERIMAAN_TP_01','REGION_PENERIMAAN_TP_04','REGION_PENERIMAAN_TP_05'))) {
				//--Masa kerja > 15 tahun
				if ($masaKerja->in_year >= configuration('CASA_CASHOUT_GIVEN')){
					if ($employee->gender_tp == 'GENDER_TP_01'){
						/* if (in_array($campSt, array(null,'0'))){
							$remainingCasa = configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp;
						}
						else {
							$remainingCasa = configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
						} */

						$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp : configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
					}
					else {
						$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * $pp : configuration('MAX_CASA_00') * $pp;
					}
				}
			}
			else {
				if ($employee->gender_tp == 'GENDER_TP_01'){
					$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp : configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
				}
				else {
					$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * $pp : configuration('MAX_CASA_00') * $pp;
				}

				/* if ($employee->gender_tp == 'GENDER_TP_01'){
					$remainingCasa = ($campSt == null or '0') ? configuration('MAX_CASA_01') * ($totalDependent + 1) : configuration('MAX_CASA_00') * ($totalDependent + 1);
				}
				else {
					$remainingCasa = ($campSt == null or '0') ? configuration('MAX_CASA_01') : configuration('MAX_CASA_00');
				} */
			}
		}
		}

		if ($remainingCasa) {
			/* $dueDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime($dateJoin)) ) );
			if ( date("n",strtotime($dueDate))<=date( "n") ) {
				$startDate = date('Y-m-d', strtotime($dueDate." +1 year"));
			}
			else {
				$startDate = date('Y-m-d', strtotime($dueDate." -1 year"));
			} */

			//--V.1 : Berdasarkan tanggal efektif join
			/* $startDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime($dateJoin)) ) );
			$trxDate = date( "Y-m-d");
			if ($startDate > $trxDate) {
				$dueDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime($dateJoin)) ) );
				$startDate = date('Y-m-d', strtotime($dueDate." -1 year"));
			} */
			//--V.2 : Berdasarkan tanggal 1 bulan join
			$startDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime('Y-m-1')) ) );
			$trxDate = date( "Y-m-d");
			if ($startDate > $trxDate) {
				$dueDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime('Y-m-1')) ) );
				$startDate = date('Y-m-d', strtotime($dueDate." -1 year"));
			}

			$casaUse = 0;
			$casa = HrEmployeeClaimCasa::select(
						DB::Raw("sum(coalesce(casa_total,1)) as casa_total")
						)
						->where('employee_cd', $employeeCd)
						->where('claim_st', 'PROSES_APPROVAL_05')
						->where('casa_tp', 'CASA_TP_01')
						->where('casa_date', '>=', $startDate)
						//->where('casa_date', '<=', $dueDate)
						->where('casa_date', '<=', $trxDate)
						->first();
			if ($casa) {
				$casaUse = $casa->casa_total;
			}

			$casaCashout = 0;
			$casaCashout = HrEmployeeClaimCasaCashout::where('employee_cd', $employeeCd)
							->whereYear('cashout_date', $year)
							->where('claim_st', 'PROSES_APPROVAL_05')
							->count();

			$remainingCasa  -= ($casaUse + $casaCashout);
			if ($remainingCasa < 0) {
				$remainingCasa = 0;
			}
			//$remainingCasa = $startDate;
		}

		//--Region tidak berhak atas casa
        /* if (in_array($regionPenerimaan, explode(',', configuration('CASA_REGION_EXCLUDE')))) {
            $remainingCasa = null;
        } */

        return array("remainingCasa" => $remainingCasa);
    }

	static function getResumeCasaYearTrx($employeeCd, $campSt, $regionPenerimaan, $dateJoin, $year)
    {
		$remainingCasa = null;
		$pp = 2;

		$employee = HrEmployee::where('employee_cd',$employeeCd)->first();

		$masaKerja = VwEmployeeMasaKerjaFull::where('employee_cd', $employeeCd)->first();
		if ($masaKerja) {
		if ($masaKerja->in_year >= 1) {

			$employeeFamily = HrEmployeeFamily::where
							('employee_cd',$employeeCd)
							->where('dependent_st', '1')
							->get();
			$totalDependent = count($employeeFamily);

			//--Region penerimaan lokal
			//if ($employee->region_penerimaan_tp == 'REGION_PENERIMAAN_TP_01'){
			if (in_array($employee->region_penerimaan_tp, array('REGION_PENERIMAAN_TP_01','REGION_PENERIMAAN_TP_04','REGION_PENERIMAAN_TP_05'))) {
				//--Masa kerja > 15 tahun
				if ($masaKerja->in_year >= configuration('CASA_CASHOUT_GIVEN')){
					if ($employee->gender_tp == 'GENDER_TP_01'){
						/* if (in_array($campSt, array(null,'0'))){
							$remainingCasa = configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp;
						}
						else {
							$remainingCasa = configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
						} */

						$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp : configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
					}
					else {
						$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * $pp : configuration('MAX_CASA_00') * $pp;
					}
				}
			}
			else {
				if ($employee->gender_tp == 'GENDER_TP_01'){
					$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp : configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
				}
				else {
					$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * $pp : configuration('MAX_CASA_00') * $pp;
				}

				/* if ($employee->gender_tp == 'GENDER_TP_01'){
					$remainingCasa = ($campSt == null or '0') ? configuration('MAX_CASA_01') * ($totalDependent + 1) : configuration('MAX_CASA_00') * ($totalDependent + 1);
				}
				else {
					$remainingCasa = ($campSt == null or '0') ? configuration('MAX_CASA_01') : configuration('MAX_CASA_00');
				} */
			}

		}
		}

		if ($remainingCasa) {
			//--V.1 : Berdasarkan tanggal efektif join
			/* $startDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime($dateJoin)) ) );
			$trxDate = date( "Y-m-d");
			if ($startDate > $trxDate) {
				$dueDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime($dateJoin)) ) );
				$startDate = date('Y-m-d', strtotime($dueDate." -1 year"));
			} */
			//--V.2 : Berdasarkan tanggal 1 bulan join
			$startDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime('Y-m-1')) ) );
			$trxDate = date( "Y-m-d");
			if ($startDate > $trxDate) {
				$dueDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime('Y-m-1')) ) );
				$startDate = date('Y-m-d', strtotime($dueDate." -1 year"));
			}

			$casaUse = 0;
			$casa = HrEmployeeClaimCasa::select(
						DB::Raw("sum(coalesce(casa_total,1)) as casa_total")
						)
						->where('employee_cd', $employeeCd)
						->where('claim_st', 'PROSES_APPROVAL_05')
						->where('casa_tp', 'CASA_TP_01')
						->where('casa_date', '>=', $startDate)
						->where('casa_date', '<=', $trxDate)
						->first();
			if ($casa) {
				$casaUse = $casa->casa_total;
			}

			$casaCashout = 0;
			$casaCashout = HrEmployeeClaimCasaCashout::where('employee_cd', $employeeCd)
							->whereYear('cashout_date', $year)
							->where('claim_st', 'PROSES_APPROVAL_05')
							->count();

			$remainingCasa  -= ($casaUse + $casaCashout);
			if ($remainingCasa < 0) {
				$remainingCasa = 0;
			}
		}

		//--Region tidak berhak atas casa
        /* if (in_array($regionPenerimaan, explode(',', configuration('CASA_REGION_EXCLUDE')))) {
            $remainingCasa = null;
        } */

        return array("remainingCasa" => $remainingCasa);
    }

	static function getResumeCasaYearPast($employeeCd, $campSt, $regionPenerimaan, $dateJoin, $year)
    {
		$remainingCasa = null;
		$pp = 2;

		$employee = HrEmployee::where('employee_cd',$employeeCd)->first();

		$masaKerja = VwEmployeeMasaKerjaFull::where('employee_cd', $employeeCd)->first();
		if ($masaKerja) {
		if ($masaKerja->in_year >= 1) {

			$employeeFamily = HrEmployeeFamily::where
							('employee_cd',$employeeCd)
							->where('dependent_st', '1')
							->get();
			$totalDependent = count($employeeFamily);

			//--Region penerimaan lokal
			//if ($employee->region_penerimaan_tp == 'REGION_PENERIMAAN_TP_01'){
			if (in_array($employee->region_penerimaan_tp, array('REGION_PENERIMAAN_TP_01','REGION_PENERIMAAN_TP_04','REGION_PENERIMAAN_TP_05'))) {
				//--Masa kerja > 15 tahun
				if ($masaKerja->in_year >= configuration('CASA_CASHOUT_GIVEN')){
					if ($employee->gender_tp == 'GENDER_TP_01'){
						/* if (in_array($campSt, array(null,'0'))){
							$remainingCasa = configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp;
						}
						else {
							$remainingCasa = configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
						} */

						$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp : configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
					}
					else {
						$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * $pp : configuration('MAX_CASA_00') * $pp;
					}
				}
			}
			else {
				if ($employee->gender_tp == 'GENDER_TP_01'){
					$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp : configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
				}
				else {
					$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * $pp : configuration('MAX_CASA_00') * $pp;
				}

				/* if ($employee->gender_tp == 'GENDER_TP_01'){
					$remainingCasa = ($campSt == null or '0') ? configuration('MAX_CASA_01') * ($totalDependent + 1) : configuration('MAX_CASA_00') * ($totalDependent + 1);
				}
				else {
					$remainingCasa = ($campSt == null or '0') ? configuration('MAX_CASA_01') : configuration('MAX_CASA_00');
				} */
			}
		}
		}

		if ($remainingCasa) {
			/* $dueDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime($dateJoin)) ) );
			if ( date("n",strtotime($dueDate))<=date( "n") ) {
				$startDate = date('Y-m-d', strtotime($dueDate." +1 year"));
			}
			else {
				$startDate = date('Y-m-d', strtotime($dueDate." -1 year"));
			} */

			//--V.1 : Berdasarkan tanggal efektif join
			/* $startDate = date( "Y-m-d", strtotime( $year-1 .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime($dateJoin)) ) );
			//$trxDate = date( "Y-m-d");
			$trxDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime($dateJoin)) ) );
			if ($startDate > $trxDate) {
				$dueDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime($dateJoin)) ) );
				$startDate = date('Y-m-d', strtotime($dueDate." -1 year"));
			} */
			//--V.2 : Berdasarkan tanggal 1 bulan join
			/* $startDate = date( "Y-m-d", strtotime( $year-1 .'-' .date("m",strtotime($dateJoin)).'-' .'-01' ) );
			$trxDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .'-01' ) );
			$trxDate = date('Y-m-d', strtotime($trxDate." -1 day"));
			if ($startDate > $trxDate) {
				$dueDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .'-01' ) );
				$startDate = date('Y-m-d', strtotime($dueDate." -1 year"));
			} */
			$startDate = date( "Y-m-d", strtotime( $year-1 .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime('Y-m-1')) ) );
			$trxDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime('Y-m-1')) ) );
			$trxDate = date('Y-m-d', strtotime($trxDate." -1 day"));
			if ($startDate > $trxDate) {
				$dueDate = date( "Y-m-d", strtotime( $year .'-' .date("m",strtotime($dateJoin)).'-' .date("d",strtotime('Y-m-1')) ) );
				$startDate = date('Y-m-d', strtotime($dueDate." -1 year"));
			}

			$casaUse = 0;
			$casa = HrEmployeeClaimCasa::select(
						DB::Raw("sum(coalesce(casa_total,1)) as casa_total")
						)
						->where('employee_cd', $employeeCd)
						->where('claim_st', 'PROSES_APPROVAL_05')
						->where('casa_tp', 'CASA_TP_01')
						->where('casa_date', '>=', $startDate)
						//->where('casa_date', '<=', $dueDate)
						->where('casa_date', '<=', $trxDate)
						->first();
			if ($casa) {
				$casaUse = $casa->casa_total;
			}

			$casaCashout = 0;
			$casaCashout = HrEmployeeClaimCasaCashout::where('employee_cd', $employeeCd)
							->whereYear('cashout_date', $year)
							->where('claim_st', 'PROSES_APPROVAL_05')
							->count();

			$remainingCasa  -= ($casaUse + $casaCashout);
			if ($remainingCasa < 0) {
				$remainingCasa = 0;
			}
		}

		//--Region tidak berhak atas casa
        /* if (in_array($regionPenerimaan, explode(',', configuration('CASA_REGION_EXCLUDE')))) {
            $remainingCasa = null;
        } */

		return array("remainingCasa" => $remainingCasa);
    }

	static function getCasaEmployee($employeeCd, $campSt, $regionPenerimaan, $dateJoin, $year)
    {
		$remainingCasa = null;
		$pp = 2;

		$employee = HrEmployee::where('employee_cd',$employeeCd)->first();

		$masaKerja = VwEmployeeMasaKerjaFull::where('employee_cd', $employeeCd)->first();
		if ($masaKerja) {
		if ($masaKerja->in_year >= 1) {

			$employeeFamily = HrEmployeeFamily::where
							('employee_cd',$employeeCd)
							->where('dependent_st', '1')
							->get();
			$totalDependent = count($employeeFamily);

			//--Region penerimaan lokal
			//if ($employee->region_penerimaan_tp == 'REGION_PENERIMAAN_TP_01'){
			if (in_array($employee->region_penerimaan_tp, array('REGION_PENERIMAAN_TP_01','REGION_PENERIMAAN_TP_04','REGION_PENERIMAAN_TP_05'))) {
				//--Masa kerja > 15 tahun
				if ($masaKerja->in_year >= configuration('CASA_CASHOUT_GIVEN')){
					if ($employee->gender_tp == 'GENDER_TP_01'){
						/* if (in_array($campSt, array(null,'0'))){
							$remainingCasa = configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp;
						}
						else {
							$remainingCasa = configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
						} */

						$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp : configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
					}
					else {
						$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') : configuration('MAX_CASA_00') * $pp;
					}
				}
			}
			else {
				if ($employee->gender_tp == 'GENDER_TP_01'){
					$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * ($totalDependent + 1) * $pp : configuration('MAX_CASA_00') * ($totalDependent + 1) * $pp;
				}
				else {
					$remainingCasa = (in_array($campSt, array(null,'0'))) ? configuration('MAX_CASA_01') * $pp : configuration('MAX_CASA_00') * $pp;
				}

				/* if ($employee->gender_tp == 'GENDER_TP_01'){
					$remainingCasa = ($campSt == null or '0') ? configuration('MAX_CASA_01') * ($totalDependent + 1) : configuration('MAX_CASA_00') * ($totalDependent + 1);
				}
				else {
					$remainingCasa = ($campSt == null or '0') ? configuration('MAX_CASA_01') : configuration('MAX_CASA_00');
				} */
			}

		}
		}

		//--Region tidak berhak atas casa
        /* if (in_array($regionPenerimaan, explode(',', configuration('CASA_REGION_EXCLUDE')))) {
            $remainingCasa = null;
        } */

        return array("remainingCasa" => $remainingCasa);
    }
}
