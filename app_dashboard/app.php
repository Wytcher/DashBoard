<?php

    class Dashboard{

        public $data_inicio;
        public $data_fim;
        public $numeroVendas;
        public $totalVendas;
        public $ativo;
        public $inativo;
        public $data_inicio_despesa;
        public $data_fim_despesa;
        public $despesas;

        public function __get($atributo){
            return $this->$atributo;
        }

        public function __set($atributo, $valor){
            $this->$atributo = $valor;
            return $this;
        }
    }

    class Conexao{
        private $host = 'localhost';
        private $dbname = 'dashboard';
        private $user = 'root';
        private $pass = '';

        public function conectar(){
            try{
                $conexao = new PDO(
                    "mysql:host=$this->host;dbname=$this->dbname",
                    "$this->user",
                    "$this->pass"
                );

                $conexao->exec('set charset set utf8');

                return $conexao;

            }catch(PDOException $e){
                echo '<p>'.$e->getMessege().'</p>';
            }
        }
    }

    class Bd{
        private $conexao;
        private $dashboard;

        public function __construct(Conexao $conexao, Dashboard $dashboard){
            $this->conexao = $conexao->conectar();
            $this->dashboard = $dashboard;
        }

        public function getNumeroVendas(){
            $query = '
            select
                count(*) as numero_vendas 
            from 
                tb_vendas 
            where 
                data_venda between :data_inicio and :data_fim';

            $stmt = $this->conexao->prepare($query);
            $stmt->bindValue(':data_inicio', $this->dashboard->__get('data_inicio'));
            $stmt->bindValue(':data_fim', $this->dashboard->__get('data_fim'));
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ)->numero_vendas;
        }

        public function getTotalVendas(){
            $query = '
            select
                SUM(total) as total_vendas
            from 
                tb_vendas 
            where 
                data_venda between :data_inicio and :data_fim';

            $stmt = $this->conexao->prepare($query);
            $stmt->bindValue(':data_inicio', $this->dashboard->__get('data_inicio'));
            $stmt->bindValue(':data_fim', $this->dashboard->__get('data_fim'));
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ)->total_vendas;
        }

        public function getAtividade(){
            $query = 'select 
                count(cliente_ativo) as ativo
            from 
                tb_clientes
            where
                cliente_ativo = 1';

            $stmt = $this->conexao->prepare($query);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ)->ativo;

        }

        public function getInatividade(){
            $query = 'select 
                count(cliente_ativo) as inativo
            from 
                tb_clientes
            where
                cliente_ativo = 0';

            $stmt = $this->conexao->prepare($query);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ)->inativo;

        }

        public function getDespesa(){
            $query = '
            select
                SUM(total) as total_despesas
            from 
                tb_despesas
            where 
                data_despesa between :data_inicio and :data_fim';

            $stmt = $this->conexao->prepare($query);
            $stmt->bindValue(':data_inicio', $this->dashboard->__get('data_inicio'));
            $stmt->bindValue(':data_fim', $this->dashboard->__get('data_fim'));
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_OBJ)->total_despesas;

        }
    }    

    //lógica do script
    $dashboard = new Dashboard();

    $conexao = new Conexao();

    $competencia = explode('-', $_GET['competencia']);
    $ano = $competencia[0];
    $mes = $competencia[1];

    $dias_do_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);

    $dashboard->__set('data_inicio', $ano.'-'.$mes.'-01');
    $dashboard->__set('data_fim', $ano.'-'.$mes.'-'.$dias_do_mes);
    $dashboard->__set('data_inicio_despesa', $ano.'-'.$mes.'-01');
    $dashboard->__set('data_fim_despesa', $ano.'-'.$mes.'-'.$dias_do_mes);

    $bd = new Bd($conexao, $dashboard);

    $dashboard->__set('numeroVendas', $bd->getNumeroVendas());
    $dashboard->__set('totalVendas', $bd->getTotalVendas());
    $dashboard->__set('despesas', $bd->getDespesa());
    $dashboard->__set('ativo', $bd->getAtividade());
    $dashboard->__set('inativo', $bd->getInatividade());
    
    echo json_encode($dashboard);


?>  