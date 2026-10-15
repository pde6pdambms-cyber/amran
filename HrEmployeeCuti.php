<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\HrEmployee;
use App\Models\HrEmployeeOrg;

class HrEmployeeCuti extends Model
{
    protected $table        = 'hrms.hr_employee_cuti';
    protected $primaryKey   = 'hr_employee_cuti_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_cd', 'cuti_tp', 'cuti_st', 'start_date', 'end_date', 'note', 'alamat', 'cuti_file', 'response', 'created_by', 'updated_by', 'created_at', 'updated_at', 'phone',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    static function getAllData()
    {
        $data = HrEmployeeCuti::select(
            'hr_employee_cuti.*',
            'cutitp.code_nm as cuti_tp_nm',
            'cutist.code_nm as cuti_st_nm',
            'employee.employee_nm as employee_nm',
            'employee.nip as nip',
            'employee.badge_no as badge_no',
            'employeeOrg.division_cd as division_cd',
            'employeeOrg.employee_tp',
            'employeeOrg.level_tp',
			DB::Raw("employee.cuti_akumulasi + ( (SELECT configuration_value FROM configurations WHERE configuration_cd='CUTI_TAHUNAN_INCREASE_PRORATE')::DECIMAL * EXTRACT(month FROM hr_employee_cuti.start_date) ) as cuti_akumulasi")
			/* DB::Raw("employee.cuti_akumulasi + ( (SELECT configuration_value FROM configurations WHERE configuration_cd='CUTI_TAHUNAN_INCREASE_PRORATE')::DECIMAL * 
				(CASE
				WHEN EXTRACT(year FROM employeeOrg.date_join) = date_part('year', now()) THEN
					EXTRACT(month FROM hr_employee_cuti.start_date) - EXTRACT(month FROM employeeOrg.date_join) + 1
				ELSE
					EXTRACT(month FROM hr_employee_cuti.start_date)
				END)
				) as cuti_akumulasi") */
			)
            ->leftJoin('com_code as cutitp', 'cutitp.com_cd', 'hr_employee_cuti.cuti_tp')
            ->leftJoin('com_code as cutist', 'cutist.com_cd', 'hr_employee_cuti.cuti_st')
            ->leftJoin('hrms.hr_employee as employee', 'employee.employee_cd', 'hr_employee_cuti.employee_cd')
            ->leftJoin('hrms.hr_employee_org as employeeOrg', function($join){
                $status = '1';
                $join->on('employeeOrg.employee_cd', 'hr_employee_cuti.employee_cd');
                $join->where('employeeOrg.active_st',$status);
            })
			->orderBy("employeeOrg.employee_tp")
			->orderBy("employee.badge_no")
			->orderBy("hr_employee_cuti.start_date");
			
        return $data;
    }

    static function getResumeCutiYear($employeeCd, $year)
    {
        $yearPrev = 1;

        $dataEmployee = HrEmployee::where('employee_cd', $employeeCd)->first();
        $cutiAkumulasi = empty($dataEmployee) ? 0 : $dataEmployee->cuti_akumulasi;

        $dateStart = date('Y-01-01');
        $prorateMonth = intval(date("m"));
		
		$dataOrg = HrEmployeeOrg::where('employee_cd', $employeeCd)
					->orderBy('date_join', 'ASC')->first();
        //$dataView = VwEmployee::find($employeeCd);
		if ($dataOrg) {
            //$dateJoin = $dataView->first_date_join;
			$dateJoin = $dataOrg->date_join;
			/* $empJoin	= date_create($dateJoin);
			$dateNow 	= date_create( date("Y-m-t", strtotime(date("Y") ."-" .date("m") ."-" .date("d"))) );
			$dateDiff   = date_diff($empJoin, $dateNow);
			$totalYear	= $dateDiff->y;
			$totalMonth	= $dateDiff->m;
			$totalDay	= $dateDiff->days; */
			
			if (date_create($dateJoin) > date_create($dateStart)) {
				//$dateStart = $dateJoin;
				
				/* $date1 = date( "Y-m-d", strtotime(date("Y",strtotime($dateJoin)) .'-' .date("m",strtotime($dateJoin)) .'-' .date("d",strtotime($dateJoin))) );
				$date2 = date( "Y-m-d", strtotime(date("Y",strtotime($dateJoin)) .'-' .date("m",strtotime($dateJoin)) .'-20') );
				if ($date1 < $date2) {
					$prorateMonth = ( date("n") - date("n", strtotime($dateJoin)) ) + 1;
				}
				else {
					$prorateMonth = ( date("n") - date("n", strtotime($dateJoin)) );
				} */
				$prorateMonth = ( date("n") - date("n", strtotime($dateJoin)) ) + 1;
			}
		}
		
		$datetime1 = date_create($dateStart);
        if ($datetime1->format('Y') != date("Y")) {
            //$datetime1 = date_create($datetime1->format('Y') .'-' .$datetime1->format('m') .'-' .$datetime1->format('d'));
            $datetime1 = date_create($year - $yearPrev . '-' . $datetime1->format('m') . '-' . $datetime1->format('d'));
        }
        /* $datetime2 = date_create(date("Y-m-d"));
        $interval = date_diff($datetime1, $datetime2);
        $intervalYear = $interval->format('%y');
        $intervalMonth = $interval->format('%m') + 1; */
		
		$prorate    = floatval(configuration('CUTI_TAHUNAN_INCREASE_PRORATE')) * $prorateMonth;
        //$prorate    = floatval(configuration('CUTI_TAHUNAN_INCREASE_PRORATE')) * intval(date("m"));
        // $prorate    = floatval(configuration('CUTI_TAHUNAN_INCREASE_PRORATE')) * (intval(date("m"))-1);
        //$prorate    = floatval(configuration('CUTI_TAHUNAN_INCREASE_PRORATE')) * ($intervalYear * 12 + $intervalMonth); --> Akumulasi cuti 1 tahun

        $daysUsed   = 0;
        $qtyUsed    = 0;
		$monthUsed  = array();
        $listCuti   = HrEmployeeCuti::where('employee_cd', $employeeCd)
            ->where('cuti_st', 'PROSES_APPROVAL_05')
            ->whereIn('cuti_tp', ['CUTI_TP_01','CUTI_TP_03'])
			->where('start_date', '>=', $datetime1)
            ->whereYear('start_date', '>=', $yearPrev)
            ->whereYear('start_date', '<=', $year)
            //->whereBetween('start_date', [$datetime1, date_create(date('Y') .'-' .date('m') .'-' .date('d'))])
            ->get();
        $qtyUsed = count($listCuti);
        foreach ($listCuti as $data) {
            $daysUsed += $data->total_day;
            $month = intval(date("m", strtotime($data->start_date)));
            if (!in_array($month, $monthUsed)) {
                //$prorate = floatval(configuration('CUTI_TAHUNAN_INCREASE_PRORATE'));
            }
        }
        //$remainingDays = intval(configuration('CUTI_TAHUNAN_MAX_DAY')) - $daysUsed + $prorate;
        $remainingDays = $cutiAkumulasi + $prorate - $daysUsed;
        $remainingQtys = intval(configuration('CUTI_TAHUNAN_MAX_USE')) - $qtyUsed;
        return array("daysUsed" => $daysUsed, "qtyUsed" => $qtyUsed, "remainingDays" => $remainingDays, "remainingQtys" => $remainingQtys);
    }
	
	static function getResumeCutiYearVal($employeeCd, $year)
    {
        $yearPrev = 1;

        $dataEmployee = HrEmployee::where('employee_cd', $employeeCd)->first();
        $cutiAkumulasi = empty($dataEmployee) ? 0 : $dataEmployee->cuti_akumulasi;

        $dateStart = date('Y-01-01');
		$prorateMonth = intval(date("m"));
		
        $dataOrg = HrEmployeeOrg::where('employee_cd', $employeeCd)
					->orderBy('date_join', 'ASC')->first();
        //$dataView = VwEmployee::find($employeeCd);
		if ($dataOrg) {
            //$dateJoin = $dataView->first_date_join;
			$dateJoin = $dataOrg->date_join;
			
			if (date_create($dateJoin) > date_create($dateStart)) {
				//$dateStart = $dateJoin;
				
				/* $date1 = date( "Y-m-d", strtotime(date("Y",strtotime($dateJoin)) .'-' .date("m",strtotime($dateJoin)) .'-' .date("d",strtotime($dateJoin))) );
				$date2 = date( "Y-m-d", strtotime(date("Y",strtotime($dateJoin)) .'-' .date("m",strtotime($dateJoin)) .'-20') );
				if ($date1 < $date2) {
					$prorateMonth = ( date("n") - date("n", strtotime($dateJoin)) ) + 1;
				}
				else {
					$prorateMonth = ( date("n") - date("n", strtotime($dateJoin)) );
				} */
				$prorateMonth = ( date("n") - date("n", strtotime($dateJoin)) ) + 1;
			}
		}

        $datetime1 = date_create($dateStart);
        if ($datetime1->format('Y') != date("Y")) {
            //$datetime1 = date_create($datetime1->format('Y') .'-' .$datetime1->format('m') .'-' .$datetime1->format('d'));
            $datetime1 = date_create($year - $yearPrev . '-' . $datetime1->format('m') . '-' . $datetime1->format('d'));
        }
       /* $datetime2 = date_create(date("Y-m-d"));
        $interval = date_diff($datetime1, $datetime2);
        $intervalYear = $interval->format('%y');
        $intervalMonth = $interval->format('%m') + 1; */
		
		$prorate    = floatval(configuration('CUTI_TAHUNAN_INCREASE_PRORATE')) * $prorateMonth;
        //$prorate    = floatval(configuration('CUTI_TAHUNAN_INCREASE_PRORATE')) * intval(date("m"));
        // $prorate    = floatval(configuration('CUTI_TAHUNAN_INCREASE_PRORATE')) * (intval(date("m"))-1);
        //$prorate    = floatval(configuration('CUTI_TAHUNAN_INCREASE_PRORATE')) * ($intervalYear * 12 + $intervalMonth); --> Akumulasi cuti 1 tahun
		
		$daysUsed   = 0;
        $qtyUsed    = 0;
        $monthUsed  = array();
        $listCuti   = HrEmployeeCuti::where('employee_cd', $employeeCd)
            ->where('cuti_st', 'PROSES_APPROVAL_05')
            ->whereIn('cuti_tp', ['CUTI_TP_01'])
			->where('start_date', '>=', $datetime1)
            ->whereYear('start_date', '>=', $yearPrev)
            ->whereYear('start_date', '<=', $year)
            //->whereBetween('start_date', [$datetime1, date_create(date('Y') .'-' .date('m') .'-' .date('d'))])
            ->get();
        $qtyUsed = count($listCuti);
        foreach ($listCuti as $data) {
            $daysUsed += $data->total_day;
            $month = intval(date("m", strtotime($data->start_date)));
            if (!in_array($month, $monthUsed)) {
                //$prorate = floatval(configuration('CUTI_TAHUNAN_INCREASE_PRORATE'));
            }
        }
        //$remainingDays = intval(configuration('CUTI_TAHUNAN_MAX_DAY')) - $daysUsed + $prorate;
        $remainingDays = $cutiAkumulasi + $prorate - $daysUsed;
        $remainingQtys = intval(configuration('CUTI_TAHUNAN_MAX_USE')) - $qtyUsed;
        return array("daysUsed" => $daysUsed, "qtyUsed" => $qtyUsed, "remainingDays" => $remainingDays, "remainingQtys" => $remainingQtys);
    }
}
