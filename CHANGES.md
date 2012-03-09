#CHANGES:

March-09-2012:
1. getId() returns integer values:
https://github.com/Haensel/Neo4Yii/commit/456da853afddd18aebdf9ad7c1c1ae3792ae7290

2. Revision of ENeo4jBatchTransaction. It directly uses neo4j's batch api so you will have to
take care of validation etc. All operations will have to be "handcrafted" either by addOperation()
or a helper method like saveNode(): https://github.com/Haensel/Neo4Yii/commit/c27e7cc716f36bf2f48f7f430f6903f43ed6c65f