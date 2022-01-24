<?php
    require(__DIR__ . '/XML2Array.class.php');
    /*  
        * CREATED BY Samuel Carrara 
        * 03.11.2021
    */
    
    /*
        *
        * DESCRIÇÃO DA CLASSE
        ---------------------------------------------------------------------------
        * Esta classe é reponsavel pela conexão com o banco de dados
        ---------------------------------------------------------------------------
        *
        * LOGS DE ALTERAÇÃO
        ---------------------------------------------------------------------------
        * DATA: 08-02-2018
        * AUTOR: Samuel Carrara
        * DESCRIÇÃO: Inicia da classe
        * Versão: v1.0
        ---------------------------------------------------------------------------
        * DATA: 03-11-2021
        * AUTOR: Samuel Carrara
        * DESCRIÇÃO: Redução da estrutura da classe
        * Versão: v1.1
        ---------------------------------------------------------------------------
    */
    class Database extends XML2Array
    {
        /**
         * Variável que contém o endereço do banco
         * @access private
         * @name $base
         */
        private $host = '';

        /**
         * Variável que contém o usuário do banco
         * @access private
         * @name $user
         */
        private $user = '';

        /**
         * Variável que contém a senha do banco
         * @access private
         * @name $password
         */
        private $password = '';

        /**
         * Variável que contém nome do banco escolhido
         * @access public
         * @name $base
         */
        public $base = '';

        /**
         * Variável que define o debug
         * @access public
         * @name $debug
         */
        private $debug = true;

        /**
         * Variável que contém a query a ser executada
         * @access public
         * @name $query
         */
        public $query;

        /**
         * Variável que contém os valores dos campos a serem preenchidos
         * @access public
         * @name $content
         */
        public $content;

        /**
         * Variável que determina se o json vai ser decodificado automaticamente
         * @access public
         * @name $decode
         */
        public $decode;

        /**
         * Variável que contém a instância da conexão
         * @access private
         * @name $instance
         */
        private $instance;

        /**
         * Variável que contém a execução do commando
         * @access private
         * @name $current
         */
        private $current;

        /**
         * Variável que contém o objeto da conexão
         * @access private
         * @name $database
         */
        private $database;

        public function __construct($debug = false)
        {
            $this->debug = $debug;
        }

        /**
         * Função que conecta com o banco credauto
         * @access private
         * @return Resource
         */
        private function connection()
        {
            $this->instance = null;
            try
            {
                $config = array(
                    PDO::ATTR_PERSISTENT => false
                );
                $conn = new PDO("mysql:host={$this->host};dbname={$this->base}", $this->user, $this->password, $config);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->instance = $conn;
            }
            catch(Exception $e)
            {
                if ($this->debug)
                {
                    echo $e->getMessage() . "\n Query: {$this->query}";
                }
                $this->instance = false;
            }
            return $this->instance;
        }

        /**
         * Função que configura os parametros
         * @access public
         * @return Void
         */
        private function execute()
        {
            $this->database = $this->connection();
            $this->current = $this
                ->database
                ->prepare($this->query);
            if (is_array($this->content))
            {
                if (!is_array(@$this->content[0]))
                {
                    $array[0] = $this->content;
                }
                else
                {
                    $array = $this->content;
                }
                $i = 1;
                foreach ($array as $value)
                {
                    if (is_array($value))
                    {
                        $fill = @$value[0];
                        $type = @$value[1];
                    }
                    else
                    {
                        $fill = $value;
                        $type = "";
                    }
                    switch ($type)
                    {
                        case 'int':
                            $fill = (int)$fill;
                            $const = PDO::PARAM_INT;
                        break;
                        case 'bool':
                            $fill = (bool)$fill;
                            $const = PDO::PARAM_BOOL;
                        break;
                        default:
                            $const = PDO::PARAM_STR;
                        break;
                    }
                    if (is_null($fill))
                    {
                        $fill = NULL;
                        $const = PDO::PARAM_NULL;
                    }
                    $this
                        ->current
                        ->bindValue($i, $fill, $const);
                    $i++;
                }
            }
            $this
                ->current
                ->execute();
        }

        /**
         * Função que realiza o select multiplo
         * @access public
         * @return Array
         */
        public function select()
        {
            try
            {
                $this->execute();
                $rows = $this
                    ->current
                    ->fetchAll(PDO::FETCH_CLASS);
                $this->disconnect();
                if ($this->decode && $rows)
                {
                    foreach ($rows as $key => $row)
                    {
                        $rows[$key] = $this->normalizeFields($row);
                    }
                }
                return $rows;
            }
            catch(Exception $e)
            {
                if ($this->debug)
                {
                    echo $e->getMessage() . "\n Query: {$this->query}";
                }
                return false;
            }
        }

        /**
         * Função que limpa um objeto
         * @access private
         * @return Object
         */
        private function object_filter_recursive($object)
        {
            if (!is_object($object)) return $object;
            foreach ($object as $key => & $value)
            {
                if ($value === "")
                {
                    $object->$key = null;
                }
                else
                {
                    if (is_object($value))
                    {
                        $value = $this->object_filter_recursive($value);
                        if (empty($value) && $value !== 0)
                        {
                            $object->$key = "";
                        }
                    }
                }
            }

            return $object;
        }

        /**
         * Função que transforma array em objeto
         * @access private
         * @return Object
         */
        private function array_to_object($array)
        {
            $obj = new stdClass;
            foreach ($array as $k => $v)
            {
                if (strlen($k))
                {
                    if (is_array($v))
                    {
                        $obj->{$k} = $this->array_to_object($v); //RECURSION
                        
                    }
                    else
                    {
                        $obj->{$k} = $v;
                    }
                }
            }
            return $obj;
        }

        /**
         * Função que limpa um array
         * @access private
         * @return Object
         */
        private function array_filter_recursive($array)
        {
            if (!is_array($array))
            {
                return $array;
            }
            foreach ($array as $key => $value)
            {
                if (empty($value))
                {
                    $value = null;
                }
                else
                {
                    $value = $this->array_filter_recursive($value);
                }
                $array[$key] = $value;
            }
            return $array;
        }

        /**
         * Função que transforma um csv em um objeto
         * @access private
         * @return Object
         */
        private function csv_to_object($csv)
        {
            $array = array();
            $lines = explode(PHP_EOL, $csv);
            if (@count($lines) && !empty($lines))
            {
                $header = str_getcsv($lines[0]);
                unset($lines[0]);
                $array = array();
                foreach ($lines as $line)
                {
                    $line = str_getcsv($line);
                    if (empty($line))
                    {
                        continue;
                    }
                    $fields = array();
                    foreach ($header as $key => $value)
                    {
                        if (isset($line[$key]))
                        {
                            $fields[$value] = $line[$key];
                        }
                    }
                    if (!empty($fields) && @count($fields))
                    {
                        $array[] = $fields;
                    }
                }

            }
            if (empty($array) || !@count($array))
            {
                $object = null;
            }
            else
            {
                $object = $this->array_to_object($array);
            }
            return $object;
        }

        /**
         * Função que mapeia os campos JSON ou XML e decodifica
         * @access public
         * @return Object
         */
        private function normalizeFields($row)
        {
            foreach ($row as $key => $value)
            {
                $re = '/[a-zA-Z0-9_\- ]+,[a-zA-Z0-9_\- ]+,[a-zA-Z0-9_\- ]+,?\n/m';
                if (@json_decode($value))
                {
                    $value = $this->object_filter_recursive(@json_decode($value));
                }
                elseif ($this->createArray($value))
                {
                    $value = $this->createArray($value);
                    $value = $this->array_filter_recursive($value);
                    $value = $this->array_to_object($value);
                }
                elseif (@preg_match($re, $value))
                {

                    $value = $this->csv_to_object($value);
                }
                $row->$key = $value;
            }
            return $row;
        }

        /**
         * Função que realiza o select individual
         * @access public
         * @return Object
         */
        public function selectOne()
        {
            try
            {
                $this->execute();
                $row = $this
                    ->current
                    ->fetchObject('stdClass');
                if ($this->decode)
                {
                    $row = $this->normalizeFields($row);
                }
                $this->disconnect();
                return $row;
            }
            catch(Exception $e)
            {
                if ($this->debug)
                {
                    echo $e->getMessage() . "\n Query: {$this->query}";
                }
                return false;
            }
        }

        /**
         * Função que realiza o update
         * @access public
         * @return Int
         */
        public function update()
        {
            try
            {
                $this->execute();
                $count = $this
                    ->current
                    ->rowCount();
                $this->disconnect();
                return $count;
            }
            catch(Exception $e)
            {
                if ($this->debug)
                {
                    echo $e->getMessage() . "\n Query: {$this->query}";
                }
                return false;
            }
        }

        /**
         * Função que realiza o insert
         * @access public
         * @return Int
         */
        public function insert()
        {
            try
            {
                $this->execute();
                $count = $this
                    ->current
                    ->rowCount();
                $this->disconnect();
                return $count;
            }
            catch(Exception $e)
            {
                if ($this->debug)
                {
                    echo $e->getMessage() . "\n Query: {$this->query}";
                }
                return false;
            }
        }

        /**
         * Função que realiza o insert
         * @access public
         * @return Int
         */
        public function insertCount()
        {
            try
            {
                $this->execute();
                $count = $this
                    ->current
                    ->rowCount();
                $this->disconnect();
                return $count;
            }
            catch(Exception $e)
            {
                if ($this->debug)
                {
                    echo $e->getMessage() . "\n Query: {$this->query}";
                }
                return false;
            }
        }

        /**
         * Função que realiza a contagem dos resultados
         * @access public
         * @return Array
         */
        public function countRows()
        {
            try
            {
                $this->execute();
                $count = $this
                    ->current
                    ->rowCount();
                $this->disconnect();
                return $count;
            }
            catch(Exception $e)
            {
                if ($this->debug)
                {
                    echo $e->getMessage() . "\n Query: {$this->query}";
                }
                return false;
            }
        }

        /**
         * Função que realiza o delete
         * @access public
         * @return Int
         */
        public function delete()
        {
            try
            {
                $this->execute();
                $count = $this
                    ->current
                    ->rowCount();
                $this->disconnect();
                return $count;
            }
            catch(Exception $e)
            {
                if ($this->debug)
                {
                    echo $e->getMessage() . "\n Query: {$this->query}";
                }
                return false;
            }
        }

        /**
         * Função que realiza o insert e retorna o ID
         * @access public
         * @return Int
         */
        public function insertId()
        {
            try
            {
                $this->execute();
                $id = $this
                    ->database
                    ->lastInsertId();
                $this->disconnect();
                return $id;
            }
            catch(Exception $e)
            {
                if ($this->debug)
                {
                    echo $e->getMessage() . "\n Query: {$this->query}";
                }
                return false;
            }
        }

        /**
         * Função que testa a conexão com o banco de dados
         * @access public
         * @return Boolean
         */
        public function testConnection()
        {
            $this->query = "SHOW TABLES";
            $rows = $this->select();
            if (@count($rows) && $rows)
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        public function disconnect()
        {
            $this->instance = null;
            $this->query = null;
            $this->content = null;
            $this->current = null;
            $this->database = null;
        }
    }
?>