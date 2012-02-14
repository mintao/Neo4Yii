<?php
class ENeo4jGraphService extends EActiveResourceConnection
{      
    
    public $host='localhost';
    public $port='7474';
    public $db='db/data';
    public $contentType="application/json";
    public $acceptType="application/json";
    public $allowNullValues=false; //neo4j doesn't allow null values
        
    public function init()
    {
        parent::init();
        $this->site=$this->host.':'.$this->port.'/'.$this->db;
    }

    /**
     * Creates an ENeo4jBatchTransaction used with this connection
     * @return ENeo4jBatchTransaction the transaction object
     */
    public function createBatchTransaction()
    {
        return new ENeo4jBatchTransaction($this);
    }

    /**
     * Query the neo4j instance with a gremlin script.
     * @param EGremlinScript the gremlin script to be sent
     * @return EActiveResourceResponse A response object holding the response of the neo4j instance.
     */
    public function queryByGremlin(EGremlinScript $gremlin)
    {
        Yii::trace(get_class($this).'.queryByGremlin()','ext.Neo4Yii.ENeo4jGraphService');
        $request=new EActiveResourceRequest;
        $request->setUri($this->site.'/ext/GremlinPlugin/graphdb/execute_script');
        $request->setMethod('POST');
        $request->setData(array('script'=>$gremlin->toString()));
        $response=$this->query($request);

        return $response;
    }          
}
?>
