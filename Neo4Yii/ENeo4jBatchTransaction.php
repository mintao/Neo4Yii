<?php

class ENeo4jBatchTransaction
{
    
    public $instances=array(); //this is an array of instances used within the transaction
    public $operations=array();
    
    private $_connection;
    
    public function __construct(EActiveResourceConnection $connection)
    {
        $this->_connection=$connection;
    }
    
    public function getConnection()
    {
        return $this->_connection;
    }
    
    /**
     * This method is used to collect all instances that are used within a transaction.
     * It is only called internally.
     * @param ENeo4jPropertyContainer $propertyContainer
     */
    protected function addToInstances(ENeo4jPropertyContainer $propertyContainer)
    {
        $this->instances[$propertyContainer->batchId]=$propertyContainer;
    }

    /**
     * Add a save operation to the transaction. You can either use this for a ENeo4jNode object or a ENeo4jRelationship
     * object. If used with validation this method will throw an ENeo4jTransactionException if one of the models fails validation.
     * @param ENeo4jPropertyContainer $propertyContainer
     * @param boolean $validate Defaults to true meaning that the model is validated before it is added to the batch colleciton
     */
    public function addSaveOperation(ENeo4jPropertyContainer $propertyContainer,$validate=true)
    {
        if($validate && !$propertyContainer->validate())
            throw new ENeo4jTransactionException('Transaction failure. One or more models of class '.get_class($propertyContainer).' did not validate!');

        if(!$propertyContainer->getIsNewResource())
            return $this->addUpdateOperation($propertyContainer);

        $propertyContainer->assignBatchId(count($this->operations));
        $this->addToInstances($propertyContainer);
                
        switch($propertyContainer)
        {
            ////SAVING NODE
            case ($propertyContainer instanceof ENeo4jNode):

                $this->operations[]=array(
                    'method'=>'POST',
                    'to'=>'/'.$propertyContainer->getResource(),
                    'body'=>$propertyContainer->getAttributesToSend(),
                    'id'=>$propertyContainer->batchId
                );
            break;

            ////SAVING RELATIONSHIP
            case ($propertyContainer instanceof ENeo4jRelationship):

                //first, check if the start and end nodes have a batch id,
                //otherwise this isn't an overall transaction (nodes were created before and can't be referenced with a batch {id})!!
                $startNodeBatchId=$propertyContainer->startNode->batchId;
                $endNodeBatchId=$propertyContainer->endNode->batchId;
                $attributesToSend=$propertyContainer->getAttributesToSend();
                
                if(isset($startNodeBatchId) && isset($endNodeBatchId))
                {
                    //has data
                    if(!empty($attributesToSend))
                    {
                        $this->operations[]=array(
                            'method'=>'POST',
                            'to'=>'{'.$startNodeBatchId.'}/relationships',
                            'body'=>array(
                                'to'=>'{'.$endNodeBatchId.'}',
                                'type'=>$propertyContainer->type,
                                'data'=>$propertyContainer->getAttributesToSend(),
                            ),
                            'id'=>$propertyContainer->batchId,);
                    }
                    else //doesn't have data
                    {
                        $this->operations[]=array(
                            'method'=>'POST',
                            'to'=>'{'.$startNodeBatchId.'}/relationships',
                            'body'=>array(
                                'to'=>'{'.$endNodeBatchId.'}',
                                'type'=>$propertyContainer->type,
                            ),
                            'id'=>$propertyContainer->batchId,);
                    }
                }
                else if(isset($startNodeBatchId) && !isset($endNodeBatchId))
                {
                    //has data
                    if(!empty($attributesToSend))
                    {
                        $this->operations[]=array(
                            'method'=>'POST',
                            'to'=>'{'.$startNodeBatchId.'}/relationships',
                            'body'=>array(
                                'to'=>$propertyContainer->endNode->self,
                                'type'=>$propertyContainer->type,
                                'data'=>$propertyContainer->getAttributesToSend(),
                            ),
                            'id'=>$propertyContainer->batchId,);
                    }
                    else //doesn't have data
                    {
                        $this->operations[]=array(
                            'method'=>'POST',
                            'to'=>'{'.$startNodeBatchId.'}/relationships',
                            'body'=>array(
                                'to'=>$propertyContainer->endNode->self,
                                'type'=>$propertyContainer->type,
                            ),
                            'id'=>$propertyContainer->batchId,);
                    }
                }
                else if(!isset($startNodeBatchId) && isset($endNodeBatchId))
                {
                    //has data
                    if(!empty($attributesToSend))
                    {
                        $this->operations[]=array(
                            'method'=>'POST',
                            'to'=>$propertyContainer->startNode->self.'/relationships',
                            'body'=>array(
                                'to'=>'{'.$endNodeBatchId.'}',
                                'type'=>$propertyContainer->type,
                                'data'=>$propertyContainer->getAttributesToSend(),
                            ),
                            'id'=>$propertyContainer->batchId,);
                    }
                    else //doesn't have data
                    {
                        $this->operations[]=array(
                            'method'=>'POST',
                            'to'=>$propertyContainer->startNode->self.'/relationships',
                            'body'=>array(
                                'to'=>'{'.$endNodeBatchId.'}',
                                'type'=>$propertyContainer->type,
                            ),
                            'id'=>$propertyContainer->batchId,);
                    }
                }
                else
                {
                    //has data
                    if(!empty($attributesToSend))
                    {
                        $this->operations[]=array(
                            'method'=>'POST',
                            'to'=>'/node/'.$propertyContainer->getStartNode()->getId().'/relationships',
                            'body'=>array(
                                'to'=>$propertyContainer->endNode->self,
                                'type'=>$propertyContainer->type,
                                'data'=>$propertyContainer->getAttributesToSend(),
                            ),
                            'id'=>$propertyContainer->batchId,
                            );
                    }
                    else //doesn't have data
                    {
                        $this->operations[]=array(
                            'method'=>'POST',
                            'to'=>'/node/'.$propertyContainer->getStartNode()->getId().'/relationships',
                            'body'=>array(
                                'to'=>$propertyContainer->endNode->self,
                                'type'=>$propertyContainer->type,
                            ),
                            'id'=>$propertyContainer->batchId,
                            );
                    } 
                }
                
            break;

        }
    }

    /**
     * Add a update operation to the transaction. This is automatically used when calling addSaveOperation() on a transaction
     * and passing a model that is not new (and will therefore be updated).
     * @param ENeo4jPropertyContainer $propertyContainer
     * @param boolean $validate Defaults to true. Validates the model and throws ENeo4jTransactionException if validation fails.
     */
    protected function addUpdateOperation(ENeo4jPropertyContainer $propertyContainer,$validate=true)
    {
        if($validate && !$propertyContainer->validate())
            throw new ENeo4jTransactionException('Transaction failure. One or more models did not validate!',500);

        $propertyContainer->assignBatchId(count($this->operations));
        $this->addToInstances($propertyContainer);

        $this->operations[]=array(
            'method'=>'PUT',
            'to'=>'/'.$propertyContainer->getResource().'/'.$propertyContainer->getId().'/properties',
            'body'=>$propertyContainer->getAttributes(),
            'id'=>$propertyContainer->batchId
        );

    }

    public function execute()
    {
        Yii::trace(get_class($this).'.execute()','ext.Neo4Yii.ENeo4jBatchTransaction');

            if($this->operations) //if there are any operations, send post request, otherwise ignore it as it would return an error by Neo4j
            {
                //clean all batchIds of the objects we used during the transaction
                foreach($this->instances as $instance)
                {
                    $instance->assignBatchId(null);
                }
                
                $request=new EActiveResourceRequest;
                $request->setUri($this->getConnection()->site.'/batch');
                $request->setMethod('POST');
                $request->setData($this->operations);
                
                $response=$this->getConnection()->execute($request);

                foreach($response->getData() as $resp)
                {
                    //we check if any id that is coming back is connected to a propertyContainer in our instances array.
                    //If so we update the object and assign the idProperty (=self)
                    if(isset($resp['id']) && isset($resp['body']['self']))
                    {
                        $instance=$this->instances[$resp['id']];
                        $propertyField=$instance->idProperty();
                        $instance->$propertyField=$resp['body']['self'];
                    }
                }

                return $response;
            }

    }
   

}

?>
