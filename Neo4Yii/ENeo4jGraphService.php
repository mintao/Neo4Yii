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
    
    /**
     * Creates a node index of given name with optional configuration
     * @param string $name The name of the index
     * @param array $config Optional index configuration used for creation
     */
    public function createNodeIndex($name,$config=array())
    {
        Yii::trace(get_class($this).'.createNodeIndex()','ext.Neo4Yii.ENeo4jGraphService');
        $request=new EActiveResourceRequest;
        $request->setUri($this->site.'/index/node');
        $request->setMethod('POST');
        empty($config) ? $request->setData(array('name'=>$name)) : $request->setData(array('name'=>$name,'config'=>$config));
        $this->execute($request);        
    }
    
    /**
     * Creates a relationship index of given name with optional configuration
     * @param string $name The name of the index
     * @param array $config Optional index configuration used for creation
     */
    public function createRelationshipIndex($name,$config=array())
    {
        Yii::trace(get_class($this).'.createRelationshipIndex()','ext.Neo4Yii.ENeo4jGraphService');
        $request=new EActiveResourceRequest;
        $request->setUri($this->site.'/index/relationship');
        $request->setMethod('POST');
        empty($config) ? $request->setData(array('name'=>$name)) : $request->setData(array('name'=>$name,'config'=>$config));
        $this->execute($request);        
    }
    
    /**
     * Delete a node index of the given name
     * @param string $name
     */
    public function deleteNodeIndex($name)
    {
        Yii::trace(get_class($this).'.deleteNodeIndex()','ext.Neo4Yii.ENeo4jGraphService');
        $request=new EActiveResourceRequest;
        $request->setUri($this->site.'/index/node/'.urlencode($name));
        $request->setMethod('DELETE');
        $this->execute($request);        
    }
    
    /**
     * Delete a relationship index of the given name
     * @param string $name
     */
    public function deleteRelationshipIndex($name)
    {
        Yii::trace(get_class($this).'.deleteRelationshipIndex()','ext.Neo4Yii.ENeo4jGraphService');
        $request=new EActiveResourceRequest;
        $request->setUri($this->site.'/index/relationship/'.urlencode($name));
        $request->setMethod('DELETE');
        $this->execute($request);        
    }
    
    /**
     * Add a node to an index. If no attributes are provided all attributes will be indexed
     * @param ENeo4jNode $node The node to be indexed
     * @param array $attributes Optional: Attributes to be indexed. Defaults to empty array, meaning all attributes of this node
     * @param string $index Optional: Name of the index to be used. Defaults to the default index of this node (specified in indexName())
     * @param boolean $update Wheter to overwrite existing entries or not, defaults to false.
     */
    public function addNodeToIndex(ENeo4jNode $node,$attributes=array(),$index=null,$update=false)
    {
        Yii::trace(get_class($this).'.addNodeToIndex()','ext.Neo4Yii.ENeo4jGraphService');
        if(is_null($index))
            $index=$node->indexName();
        
        $trans=$this->createBatchTransaction();
        empty($attributes) ? $trans->addIndexOperation($node,$node->getAttributesToSend(),$index,$update) : $trans->addIndexOperation($node,$attributes,$index,$update);
        $trans->execute();       
    }
    
    /**
     * Remove a node entry from an index
     * @param ENeo4jNode $node The node to be removed
     * @param array $attributes Optional: Attributes to be removed. Defaults to empty array meaning all attributes of this node will be removed
     * @param type $index Optional: Name of the index to be used. Defaults to the default index of this node (specified in indexName())
     */
    public function removeNodeFromIndex(ENeo4jNode $node,$attributes=array(),$index=null)
    {
        if(is_null($index))
            $index=$node->indexName();
        
        $trans=$this->createBatchTransaction();
        $trans->addRemoveFromIndexOperation($node,$attributes,$index);
        $trans->execute();    
    }
    
    /**
     * Add a relationship to an index. If no attributes are provided all attributes will be indexed
     * @param ENeo4jRelationship $relationship The relationship to be indexed
     * @param array $attributes Optional: Attributes to be indexed. Defaults to empty array, meaning all attributes of this relationship
     * @param string $index Optional: Name of the index to be used. Defaults to the default index of this relationship (specified in indexName())
     * @param boolean $update Wheter to overwrite existing entries or not, defaults to false.
     */
    public function addRelationshipToIndex(ENeo4jRelationship $relationship,$attributes=array(),$index=null,$update=false)
    {
        Yii::trace(get_class($this).'.addRelationshipToIndex()','ext.Neo4Yii.ENeo4jGraphService');
        if(is_null($index))
            $index=$relationship->indexName();
        $trans=$this->createBatchTransaction();
        empty($keyValuePairs) ? $trans->addIndexOperation($relationship,$relationship->getAttributesToSend(),$index,$update) : $trans->addIndexOperation($relationship,$attributes,$index,$update);
        $trans->execute();         
    }
    
    /**
     * Remove a relationship entry from an index
     * @param ENeo4jRelationship $node The relationship to be removed
     * @param array $attributes Optional: Attributes to be removed. Defaults to empty array meaning all attributes of this relationship will be removed
     * @param type $index Optional: Name of the index to be used. Defaults to the default index of this relationship (specified in indexName())
     */
    public function removeRelationshipFromIndex(ENeo4jRelationship $relationship,$attributes=array(),$index=null)
    {
        if(is_null($index))
            $index=$relationship->indexName();
        
        $trans=$this->createBatchTransaction();
        $trans->addRemoveFromIndexOperation($relationship,$attributes,$index);
        $trans->execute();   
    }    
}
?>
