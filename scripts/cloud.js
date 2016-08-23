/**
 * D3 script for the Word Cloud WordPress shortcode and widget.
 *
 * @package    d3-word-cloud-widget
 * @author     Crystal Barton <atrus1701@gmail.com>
 * @version    1.0
 */


/**
 * Setup the Word Cloud control.
 * @param  DivElement  div  The Word Cloud control container.
 */
function d3_word_cloud( div )
{
	var self = {};
	
	self.id = '#'+div.id;
	self.div = div;
	self.words;
	self.used_words;
	self.word_count;
	self.tag_count;
	
	// Draw the terms in the word cloud.
	self.draw = function( data, bounds )
	{
		self.word_count = data.length;
		self.used_words = [];
		for( var i = 0; i < data.length; i++ )
		{
			self.used_words.push( data[i].text );
		}
		
		var anchors = self.vis
			.selectAll( 'a' )
				.data( data )
				.enter()
				.append( 'a' )
					.attr( 'xlink:href', function(d,i) { return self.words[d.text].url; } );
		
		anchors
			.append( 'title' )
				.text( function(d) { return self.words[d.text].count+' matches'; } );
		
		anchors
			.append( 'text' )
				.attr( 'text-anchor', 'middle' )
				.attr( 'transform', function(d) { return 'translate(' + [d.x, d.y] + ')rotate(' + d.rotate + ')'; } )
				.style( 'font-size', function(d) { return d.size + 'px'; } )
				.style( 'font-family', function(d) { return d.font; } )
				.style( 'fill', function(d) { return self.fill( self.words[d.text].count ); } )
				.text( function(d) { return d.text; } );
		
		var s = "";
		for( var word in self.words )
		{
			if( self.used_words.indexOf(word) == -1 )
			{
				s += word+" ["+self.words[word].count+"]<br/>";
			}
		}

		var hide_debug = unescape( div.getElementsByClassName('hide-debug')[0].value );
		var div_style = 'text-align:left;';
		if( hide_debug == 'yes' )
			div_style += 'display:none;';
			
		d3.select( self.id )
			.append( 'div' )
				.attr( 'class', 'debug-data' )
				.attr( 'style', div_style )
				.html( self.word_count+' of '+self.tag_count+' were placed.  Words not placed:<br/>'+s );
	}
	
	// Process the word cloud settings and start the layout.
	self.process_cloud = function()
	{
		var font_family = unescape( div.getElementsByClassName('font-family')[0].value );

		var font_size = unescape( div.getElementsByClassName('font-size')[0].value );
		font_size = font_size.split(',');
		if( font_size.length < 2 )
		{
			if( font_size.length == 1 ) font_size = [ font_size[0], font_size[0] ];
			else font_size = [ 10, 100 ];
		}
		font_size = d3.scale['log']().range( font_size );

		var font_color = unescape( div.getElementsByClassName('font-color')[0].value );
		font_color = font_color.split(',');
	
		var tags = unescape( div.getElementsByClassName('tags')[0].value );
		tags = JSON.parse( tags );

		var canvas = d3.select( div.getElementsByTagName('svg')[0] );
		var width = window.getComputedStyle( canvas[0][0] ).width.replace('px','');
		var height = window.getComputedStyle( canvas[0][0] ).height.replace('px','');

		canvas_size = [ +width, +height ];

		var layout = d3.layout.cloud()
			.timeInterval( 10 )
			.spiral( 'rectangular' )
			.size( canvas_size )
			.font( font_family )
			.fontSize( function(d) { return font_size(+d.count); } )
			.text( function(d) { return d.name; } )
			.on( 'end', self.draw );

		var orientation = unescape( div.getElementsByClassName('orientation')[0].value );
		if( orientation == 'random' )
		{
			var r = Math.round(Math.random() * 4);
			var o = [ 'horizontal', 'vertical', 'mixed', 'mostly-horizontal', 'mostly-vertical' ];
			orientation = o[r];
		}
		switch( orientation )
		{
			case( 'horizontal' ): layout.rotate( 0 ); break;
			case( 'vertical' ): layout.rotate( 270 ); break;
			case( 'mixed' ):
				layout.rotate(
					function()
					{
						return Math.round(Math.random()) * 270;
					});
				break;
			case( 'mostly-horizontal' ): 
				layout.rotate(
					function()
					{
						var r = Math.round(Math.random() * 2);
						if( r > 1 ) return 270;
						return 0;
					});
				break;
			case( 'mostly-vertical' ):
				layout.rotate(
					function()
					{
						var r = Math.round(Math.random() * 2);
						if( r > 1 ) return 0;
						return 270;
					});
				break;
			default: layout.rotate( 0 ); break;
		}

		var background = canvas.append( 'g' );
		var vis = canvas.append( 'g' )
			.attr( 'transform', 'translate(' + [canvas_size[0] >> 1, canvas_size[1] >> 1] + ')' );

		if( !tags.length ) return;

		var min = d3.min( tags, function(d) { return +d.count; } );
		var max = d3.max( tags, function(d) { return +d.count; } );
		var quantize = d3.scale.quantize()
			.domain( [0, max] )
			.range( d3.range(20).map(function(i) { return 'd3-word-cloud-text-'+i; }) );

		if( font_color.length == 1 ) font_color = [ font_color[0], font_color[0] ];
		var increment = max / font_color.length+1;
		var fill_domain = [];
		for( var i = 0; i < font_color.length; i++ ) { fill_domain.push(increment*i); }
		if( fill_domain.length == 1 ) fill_domain = [ fill_domain[0], max ];

		var fill = d3.scale.linear().domain(fill_domain).range(font_color);
		font_size.domain( [min, max] );

		layout.stop().words( tags );

		var words = {};
		for( var i = 0; i < tags.length; i++ )
		{
			words[ tags[i].name ] = tags[i];
		}
	
		self.tag_count = tags.length;
		self.words = words;
		self.vis = vis;
		self.fill = fill;
	
		layout.start();
	}

	self.process_cloud();
}


// Process each D3 Word Cloud controls.
window.onload = function()
{
	var divs = d3.selectAll('.word-cloud-control');
	var clouds = [];
	
	for( var i = 0; i < divs[0].length; i++ )
	{
		var cloud = new d3_word_cloud( divs[0][i] );
		clouds.push( cloud );
	}
}

