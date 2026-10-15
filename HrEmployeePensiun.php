<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class HrEmployeePensiun extends Model
{
    protected $table        = 'hrms.hr_employee_pensiun';
    protected $primaryKey   = 'hr_employee_pensiun_id';

    protected $fillable = [
        'hr_employee_pensiun_id', 'employee_cd', 'doc_ps', 'doc_path', 'accept_st', 'created_by', 'updated_by', 'created_at', 'updated_at',
    ];

    protected $hidden = [];

    static function getAllData()
    {
        $data = HrEmployeePensiun::select(
					'hr_employee_pensiun.employee_cd',
					'employee.employee_nm',
					'employee.nip',
					'employee.badge_no as badge_no'
				)
				->leftJoin('hrms.hr_employee as employee', 'employee.employee_cd', 'hr_employee_pensiun.employee_cd')
				//->whereNull('employee.mutasi_st')
				->whereNotIn('employee.mutasi_st',['MUTASI_TP_03','MUTASI_TP_04','MUTASI_TP_05'])
				->orderBy('employee.badge_no')
				->orderBy('hr_employee_pensiun.employee_cd');
				//->distinct('hr_employee_pensiun.employee_cd');

        return $data;
    }

    static function getBerkas($employee_cd)
    {
        $berkas = 'DOC_PS';

        $data = DB::table('com_code')->select(
					'com_code.com_cd',
					'com_code.code_nm',
					'pensiun.hr_employee_pensiun_id',
					'pensiun.doc_path',
					'pensiun.accept_st',
					'pensiun.employee_cd'
				)
				->leftJoin('hrms.hr_employee_pensiun as pensiun', function ($join) use ($employee_cd, $berkas) {
					$join->on('com_code.com_cd', '=', 'pensiun.doc_ps');
					$join->on('pensiun.employee_cd', '=', DB::raw("'" . $employee_cd . "'"));
				})
				->where('com_code.code_group', DB::raw("'" . $berkas . "'"))
				->get();

        return $data;
    }

    static function getNominatif()
    {
        $first = DB::table('hrms.hr_employee')->select(
					'hr_employee.employee_cd',
					'hr_employee.employee_nm',
					'hr_employee.nip',
					'hr_employee.badge_no',
					'org.employee_tp',
					'employeetp.code_nm as employee_tp_nm',
					'hr_employee.gender_tp',
					'gendertp.code_nm as gender_tp_nm',
					'staff.staff_cd',
					'staff.staff_nm',
					'staff.eselon_cd',
					'eseloncd.code_nm as eselon_cd_nm',
					'pangkat.golongan_jabatan',
					'gol.code_nm as golongan_jabatan_nm',
					'hr_employee.birth_date',
					DB::raw("extract(year from hr_employee.birth_date + interval '" . configuration('USIA_PENSIUN_03') . " year 1 day') as tahun_pensiun"),
					'long.pensiun_tgl','long.no_sk',
					'long.pensiun_tgl2','long.no_sk2',
					'long.pensiun_tgl3','long.no_sk3',
					'long.pensiun_tgl4','long.no_sk4',
					'long.pensiun_tgl5','long.no_sk5',
				)
				->leftJoin('hrms.hr_employee_pensiunlong as long', 'long.employee_cd', 'hr_employee.employee_cd')
				->leftJoin('hrms.hr_employee_org as org', function ($join) {
					$join->on('org.employee_cd', '=', 'hr_employee.employee_cd');
					$join->on('org.active_st', '=', DB::raw('1::character varying'));
				})
				->leftJoin('com_code as employeetp', 'employeetp.com_cd', 'org.employee_tp')
				->leftJoin('com_code as gendertp', 'gendertp.com_cd', 'hr_employee.gender_tp')
				->leftJoin('hrms.hr_company_staff as staff', 'staff.staff_cd', 'org.staff_cd')
				->leftJoin('com_code as eseloncd', 'eseloncd.com_cd', 'staff.eselon_cd')
				->leftJoin('hrms.hr_employee_pangkat as pangkat', function ($join) {
					$join->on('pangkat.employee_cd', '=', 'hr_employee.employee_cd');
					$join->on('pangkat.active_st', '=', DB::raw('1::character varying'));
				})
				->leftJoin('com_code as gol', 'gol.com_cd', 'pangkat.golongan_jabatan')
				->whereBetween(DB::raw("extract(year from hr_employee.birth_date + interval '" . configuration('USIA_PENSIUN_03') . " year 1 day') - extract(year from now())"), [-4, 5])
				->whereNotIn('hr_employee.mutasi_st',['MUTASI_TP_03','MUTASI_TP_04','MUTASI_TP_05']);
				//->whereNull('hr_employee.mutasi_st');

        $data = DB::table('hrms.hr_employee')->select(
					'hr_employee.employee_cd',
					'hr_employee.employee_nm',
					'hr_employee.nip',
					'hr_employee.badge_no',
					'org.employee_tp',
					'employeetp.code_nm as employee_tp_nm',
					'hr_employee.gender_tp',
					'gendertp.code_nm as gender_tp_nm',
					'staff.staff_cd',
					'staff.staff_nm',
					'staff.eselon_cd',
					'eseloncd.code_nm as eselon_cd_nm',
					'pangkat.golongan_jabatan',
					'gol.code_nm as golongan_jabatan_nm',
					'hr_employee.birth_date',
					DB::raw("extract(year from hr_employee.birth_date + interval '" . configuration('USIA_PENSIUN_03') . " year 1 day') as tahun_pensiun"),
					'long.pensiun_tgl','long.no_sk',
					'long.pensiun_tgl2','long.no_sk2',
					'long.pensiun_tgl3','long.no_sk3',
					'long.pensiun_tgl4','long.no_sk4',
					'long.pensiun_tgl5','long.no_sk5',
				)
				->leftJoin('hrms.hr_employee_pensiunlong as long', 'long.employee_cd', 'hr_employee.employee_cd')
				->leftJoin('hrms.hr_employee_org as org', function ($join) {
					$join->on('org.employee_cd', '=', 'hr_employee.employee_cd');
					$join->on('org.active_st', '=', DB::raw('1::character varying'));
				})
				->leftJoin('com_code as employeetp', 'employeetp.com_cd', 'org.employee_tp')
				->leftJoin('com_code as gendertp', 'gendertp.com_cd', 'hr_employee.gender_tp')
				->leftJoin('hrms.hr_company_staff as staff', 'staff.staff_cd', 'org.staff_cd')
				->leftJoin('com_code as eseloncd', 'eseloncd.com_cd', 'staff.eselon_cd')
				->leftJoin('hrms.hr_employee_pangkat as pangkat', function ($join) {
					$join->on('pangkat.employee_cd', '=', 'hr_employee.employee_cd');
					$join->on('pangkat.active_st', '=', DB::raw('1::character varying'));
				})
				->leftJoin('com_code as gol', 'gol.com_cd', 'pangkat.golongan_jabatan')
				->whereBetween(DB::raw("extract(year from hr_employee.birth_date + interval '" . configuration('USIA_PENSIUN_03') . " year 1 day') - extract(year from now())"), [-4, 5])
				->whereNull('hr_employee.mutasi_st')
				->union($first);

        return $data;
    }
}
