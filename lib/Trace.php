<?php
	
	class LayerCache_Trace
	{
		public 
			$time, 
			$key, 
			$cache_count, 
			$flat_key, 
			$rand, 
			$reads = array(), 
			$source = array(), 
			$writes = array();
	}
	