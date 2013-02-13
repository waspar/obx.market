if(typeof(OBX) == 'undefined') {
	OBX = {};
}
OBX.getSerializedFormAsObject = (function() {
	if( typeof(OBX.getSerializedFormAsObject) == 'function' ) {
		return OBX.getSerializedFormAsObject;
	}
	return function(arFormFields) {
		var getParentNodeByNameChain = function(obFieldsTree, arNameChain, depth) {
			var parentNode = obFieldsTree;
			for(var index=1; index<=depth; index++) {
				//var dumpCurParent = parentNode;
				parentNode = parentNode[arNameChain[index-1]];
			}
			return parentNode;
		}

		var obFieldsTree = {};
		for(key in arFormFields) {
			var formParamName = arFormFields[key].name;
			var formParamValue = arFormFields[key].value;

			var arFormParamNameChain = formParamName.split('[');
			var arAutoIndex = {};
			for(var depth=0; depth<arFormParamNameChain.length; depth++) {
				// removing last "]" from names
				if( arFormParamNameChain[depth][arFormParamNameChain[depth].length-1] == ']' ) {
					arFormParamNameChain[depth] = arFormParamNameChain[depth].substr(0, arFormParamNameChain[depth].length-1);
				}
				var curIndexName = arFormParamNameChain[depth];

				// makeing tree from name-chain
				if(arFormParamNameChain.length - depth == 1) {
					var parentNode = null;
					if(curIndexName == '') {
						parentNode = getParentNodeByNameChain(obFieldsTree, arFormParamNameChain, depth);
						parentNode.__itemsCount__++
						parentNode[parentNode.__itemsCount__] = formParamValue;
					}
					else {
						parentNode = getParentNodeByNameChain(obFieldsTree, arFormParamNameChain, depth);
						parentNode[curIndexName] = formParamValue;
					}
					break;
				}
				else {
					parentNode = getParentNodeByNameChain(obFieldsTree, arFormParamNameChain, depth);
					if( parentNode[curIndexName] == undefined ) {
						parentNode[curIndexName] = {__itemsCount__: 0};
					}

				}
			}
		}
		var removeItemsCount = function(obTree) {
			for(key in obTree) {
				if(key == '__itemsCount__') {
					delete obTree[key];
				}
				else if(obTree[key].constructor == Object) {
					removeItemsCount(obTree[key]);
				}
			}
			return obTree;
		}
		removeItemsCount(obFieldsTree);
		delete removeItemsCount;
		delete getParentNodeByNameChain;
		return obFieldsTree;
	};
})();