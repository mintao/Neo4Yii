<?php

class ENeo4jBatchTransaction
{
    private $_operations=array();
    
    private $_connection;
    
    public function __construct(EActiveResourceConnection $connection)
    {
        $this->_connection=$connection;
    }
    
    public function getConnection()
    {
        return $this->_connection;
    }
    
    public function addOperation($method,$to,$body=array())
    {
        $batchId=count($this->_operations);
        $this->_operations[]=array(
                'method'=>$method,
                'to'=>$to,
                'id'=>$batchId,
                'body'=>$body);
        return $batchId;
    }
    
    public function getNode($node)
    {
        return $this->addOperation('GET','node/'.$node);
    }
    
    public function getRelationship($relationship)
    {
        return $this->addOperation('GET','relationship/'.$relationship);
    }
    
    public function createNode($attributes=array())
    {
        return $this->addOperation('POST','node',$attributes);
    }
    
    public function createRelationship($fromNode,$toNode,$type,$attributes=array())
    {
        if(strpos($fromNode,'{')===false)
            $fromNode='node/'.$fromNode;
        if(strpos($toNode,'{')===false)
            $toNode='node/'.$toNode;
        if(empty($attributes))
            return $this->addOperation('POST',$fromNode.'/relationships',array('to'=>$toNode,'type'=>$type));
        else
            return $this->addOperation('POST',$fromNode.'/relationships',array('to'=>$toNode,'type'=>$type,'data'=>$attributes));
    }
    
    public function deleteNode($node)
    {
        return $this->addOperation('DELETE','node/'.$node);
    }
    
    public function deleteRelationship($relationship)
    {
        return $this->addOperation('DELETE','relationship/'.$relationship);
    }
            
    /**
     * Adds a node to a defined index with given attributes.
     * @param integer $nodeId The id of the node to be indexed.
     * @param array $attributes The attributes to be indexed
     * @param string $index The name of the index to be used
     * @param boolean $update Set to true if you want to replace existing index entries. Defaults to false, meaning adding index entries, even if they already exist
     */
    public function indexNode($nodeId,$attributes,$index,$update=false)
    {
        if($update)
        {
            foreach($attributes as $key=>$value)
                $this->addOperation('DELETE', '/index/node/'.urlencode($index).'/'.urlencode($key).'/'.$nodeId);
        }
            
        foreach($attributes as $key=>$value)
            $this->addOperation('POST','/index/node/'.urlencode($index),array('uri'=>$this->getConnection()->site.'/'.$nodeId,'key'=>$key,'value'=>$value));
    }
    
    /**
     * Adds a relationship to a defined index with given attributes.
     * @param integer $relationshipId The id of the node to be indexed.
     * @param array $attributes The attributes to be indexed
     * @param string $index The name of the index to be used
     * @param boolean $update Set to true if you want to replace existing index entries. Defaults to false, meaning adding index entries, even if they already exist
     */
    public function indexRelationship($relationshipId,$attributes,$index,$update=false)
    {        
        if($update)
        {
            foreach($attributes as $key=>$value)
                $this->addOperation ('DELETE','/index/relationship/'.urlencode($index).'/'.urlencode($key).'/'.$relationshipId);
        }
            
        foreach($attributes as $key=>$value)
            $this->addOperation('POST', '/index/relationship/'.urlencode($index),array('uri'=>$this->getConnection()->site.'/'.$relationshipId,'key'=>$key,'value'=>$value));
    }
    
    /**
     * Removes a property container from an index. If attributes are supplied only entries for given attribute keys will be deleted
     * If no attributes are given, all entries for the given property container will be deleted.
     * @param integer $nodeId The id of the node to be removed from the index
     * @param string $index Name of the index this operation will be using
     * @param array $attributes Optional: An array of attributes to be deleted for this property container. If none are provided the node will be removed for all indexed properties
     */
    public function removeNodeFromIndex($nodeId,$index,$attributes=array())
    {        
        if(!empty($attributes))
        {
            //only delete entries for given attributes
            foreach($attributes as $key=>$value)
                $this->addOperation('DELETE','/index/node/'.urlencode($index).'/'.$key.'/'.$nodeId);
        }
        else
        {
            //delete all entries for this property container
            $this->addOperation('DELETE','/index/node/'.urlencode($index).'/'.$nodeId);
        }
    }
    
    /**
     * Removes a property container from an index. If attributes are supplied only entries for given attribute keys will be deleted
     * If no attributes are given, all entries for the given property container will be deleted.
     * @param integer $relationshipId The id of the node to be removed from the index
     * @param string $index Name of the index this operation will be using
     * @param array $attributes Optional: An array of attributes to be deleted for this property container. If none are provided the node will be removed for all indexed properties
     */
    public function removeRelationshipFromIndex($relationshipId,$index,$attributes=array())
    {        
        if(!empty($attributes))
        {
            //only delete entries for given attributes
            foreach($attributes as $key=>$value)
                $this->addOperation('DELETE','/index/relationship/'.urlencode($index).'/'.$key.'/'.$relationshipId);
        }
        else
        {
            //delete all entries for this property container
            $this->addOperation('DELETE', '/index/relationship/'.urlencode($index).'/'.$relationshipId);
        }
    }

    public function execute()
    {
        Yii::trace(get_class($this).'.execute()','ext.Neo4Yii.ENeo4jBatchTransaction');
        if($this->_operations) //if there are any operations, send post request, otherwise ignore it as it would return an error by Neo4j
        {
            $request=new EActiveResourceRequest;
            $request->setUri($this->getConnection()->site.'/batch');
            $request->setMethod('POST');
            $request->setData($this->_operations);

            return $this->getConnection()->execute($request);                
        }
    }
}

?>
