<?php



/**
 * Questions model.
 */
class Questions extends NObject
{
	/** @var string */
	private $_questions_table 	= 'question';
	private $_answers_table		= 'answer';
	private $_attachments_table = 'question_attachment';

	/** @var DibiConnection */
	private $connection;


	public static function initialize()
	{
		dibi::connect(Environment::getConfig('database'));
	}

	public function __construct()
	{
		$this->connection = dibi::getConnection();
	}



	public function findAll()
	{
		return $this->connection->_query('SELECT t1.*, t2.id AS answers_id, t2.value_en, t2.value_sk, t2.correct, t3.id AS attachments_id, t3.name AS attachments_name, t3.value AS attachments_value, t3.type AS attachments_type FROM %s AS t1 LEFT JOIN %s AS t2 ON t1.id = t2.question_id LEFT JOIN %s AS t3 ON  t1.id = t3.question_id', $this->_questions_table, $this->_answers_table, $this->_attachments_table);
	}



	public function find($id)
	{
		return $this->connection->_query('SELECT t1.*, t2.id AS answers_id, t2.value_en, t2.value_sk, t2.correct, t3.id AS attachments_id, t3.name AS attachments_name, t3.value AS attachments_value, t3.type AS attachments_type FROM %s AS t1 LEFT JOIN %s AS t2 ON t1.id = t2.question_id LEFT JOIN %s AS t3 ON  t1.id = t3.question_id WHERE t1.id = %i', $this->_questions_table, $this->_answers_table, $this->_attachments_table, $id);
  	}



	public function update($id, array $data)
	{
		return $this->connection->update($this->_questions_table, $data)->where('id=%i', $id)->execute();
	}


	public function update_answer($id, array $data)
	{
		return $this->connection->update($this->_answers_table, $data)->where('id=%i', $id)->execute();
	}



	public function update_attachment($id, array $data)
	{
		return $this->connection->update($this->_attachments_table, $data)->where('id=%i', $id)->execute();
	}



	public function insert(array $data)
	{
		return $this->connection->insert($this->_questions_table, $data)->execute(dibi::IDENTIFIER);
	}


	public function insert_answer(array $data)
	{
		return $this->connection->insert($this->_answers_table, $data)->execute(dibi::IDENTIFIER);
	}

	public function insert_attachment(array $data)
	{
		return $this->connection->insert($this->_attachments_table, $data)->execute(dibi::IDENTIFIER);
	}

	public function delete($id)
	{
		return $this->connection->delete($this->_questions_table)->where('id=%i', $id)->execute();
	}

	public function delete_answer($id)
	{
		return $this->connection->delete($this->_answers_table)->where('id=%i', $id)->execute();
	}

	public function delete_attachment($id)
	{
		return $this->connection->delete($this->_attachments_table)->where('id=%i', $id)->execute();
	}

}