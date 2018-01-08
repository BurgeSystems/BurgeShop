<?php
class Coupon_manager_model extends CI_Model {
	private $coupon_table_name="coupon";
	private $coupon_payment_table_name="coupon_payment";

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$table=$this->db->dbprefix($this->coupon_table_name); 
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS $table (
				`coupon_id` int AUTO_INCREMENT 
				,`coupon_name` char(63) DEFAULT NULL
				,`coupon_code` char(63) DEFAULT NULL
				,`coupon_ative` BIT(1) DEFAULT 0
				,`coupon_min_price` INT DEFAULT 0
				,`coupon_expiration_date` char(20) DEFAULT NULL
				,`coupon_value` INT DEFAULT 0
				,`coupon_value_type` enum('percent','currency') DEFAULT 'currency'
				,`coupon_customers` varchar(1024) DEFAULT NULL
				,`coupon_usage_number` INT DEFAULT 1
				,PRIMARY KEY (coupon_id)	
			) ENGINE=InnoDB DEFAULT CHARSET=utf8"
		);

		$table=$this->db->dbprefix($this->coupon_payment_table_name); 
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS $table (
				`cp_coupon_id` INT
				,`cp_order_id` INT
				,`cp_value` INT DEFAULT 0
				,PRIMARY KEY (cp_coupon_id,cp_order_id)	
			) ENGINE=InnoDB DEFAULT CHARSET=utf8"
		);

		$this->load->model("module_manager_model");
		$this->module_manager_model->add_module("coupon","coupon_manager");
		$this->module_manager_model->add_module_names_from_lang_file("coupon");
		
		return;
	
	}

	public function uninstall()
	{

		return;
	}	

	public function get_all()
	{
		return $this->db
			->select($this->coupon_table_name.".*")
			->from($this->coupon_table_name)
			->order_by("coupon_id DESC")
			->get()
			->result_array();
	}

	public function get_coupon($coupon_id)
	{
		$info=$this->db
			->select($this->coupon_table_name.".*")
			->from($this->coupon_table_name)
			->where("coupon_id",$coupon_id)
			->get()
			->row_array();

		$customers=-1;
		if($info && $info['coupon_customers'] && ($info['coupon_customers']!=-1))
		{		
			$this->load->model('customer_manager_model');
			$customers=$this->customer_manager_model->get_customers(array(
				"id"=>explode(",",$info['coupon_customers'])
			));
		}

		$orders=array();
		if($info)
		{
			$orders=$this->db
				->select($this->coupon_payment_table_name.".* , customer_name, customer_id")
				->from($this->coupon_payment_table_name)
				->join("order","cp_order_id = order_id")
				->join("customer", "order_customer_id = customer_id")
				->where("cp_coupon_id",$coupon_id)
				->get()
				->result_array();
		}

		return array($info, $customers, $orders);
	}

	public function add_new()
	{
		$props=array(
			"coupon_name"						=> ""
			,"coupon_expiration_date"		=> get_current_time()
		);

		$this->db->insert($this->coupon_table_name,$props);

		$coupon_id=$this->db->insert_id();
		$props['coupon_id']=$coupon_id;

		$this->log_manager_model->info("COUPON_ADD",$props);

		return $coupon_id;
	}

	public function set_props($coupon_id, $props)
	{
		$this->db
			->set($props)
			->where("coupon_id", $coupon_id)
			->update($this->coupon_table_name);

		$props['coupon_id']=$coupon_id;
		$this->log_manager_model->info("COUPON_CHANGE",$props);

		return;
	}

	public function delete($coupon_id)
	{		
		$this->db
			->where("coupon_id", $coupon_id)
			->delete($this->coupon_table_name);

		$props=array("coupon_id"=>$coupon_id);
		$this->log_manager_model->info("COUPON_DELETE",$props);

		return;
	}
}