<?php

namespace Wikibase\RDF;

/**
 * Writer interface for RDF output. RdfWriter instances generally stateful, but should
 * be implemented to operate in a stream-like manner with a minimum of state.
 *
 * Caveats:
 * - no relative uris
 * - predicates must be qnames
 * - no inline/nested blank nodes
 * - no comments
 * - no collections
 * - no automatic xsd types
 * - no automatic conversion of uris to qnames
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface RdfWriter {

	//TODO: "Writer" -> "Writer"? "Builder"?
	//TODO: generic RdfWriter class with shorthands, use RdfFormatters for output

	//TODO: dummy ->and()  does nothing, returns this.
	//TODO: shorthand ->a( 'foo:bar' )
	//TODO: about( 'foo:bar' ) <-> about( 'foo', 'bar' ). Same for predicate and object.
	//TODO: "say" -> "verb"; "is" -> "resource".
	//TODO: prop(  'foo:bar', 'x,y' )   (2 or 4 params, never 3!)
	//TODO: vprop(  'foo:bar', 'literal', 'x:y' )   (3 or 5 params, never 4!)
	//TODO: tprop(  'foo:bar', 'literal', 'x:y' )   (3 or 5 params, never 4!)

	//TODO: repeated about() the same thing should be ignored.

	/**
	 * @param string|null $label node label, will be generated if not given.
	 *
	 * @return string A qname for the blank node.
	 */
	public function blank( $label = null );

	/**
	 * Emit a document header. Must be paired with a later call to drain().
	 */
	public function start();

	/**
	 * Emit a document footer. Must be paired with a prior call to start().
	 *
	 * @return string The RDF output
	 */
	public function drain();

	/**
	 * Emit a prefix declaration.
	 * May remember the prefix and URI for later use.
	 * Implementations are free to fail if prefix() is called after the first call to about().
	 *
	 * @note Depending on implementation, re-definitions of prefixes may fail silently.
	 *
	 * @param string $prefix
	 * @param string $uri a reference container as returned by uri()
	 */
	public function prefix( $prefix, $uri );

	/**
	 * Start an about clause. May or may not immediately write anything.
	 * Must be preceded by a call to start().
	 * May remember the subject reference for later use.
	 *
	 * @param string $subject a resource reference (URI or QName)
	 *
	 * @return RdfWriter $this
	 */
	public function about( $subject );

	/**
	 * Start a predicate clause. May or may not immediately write anything.
	 * Must be preceded by a call to about().
	 * May remember the verb reference for later use.
	 *
	 * @todo: rename to verb() maybe?
	 *
	 * @param string $verb a QName or the shorthand "a" for rdf:type; Implementations
	 *        may or may not support full URIs to be given here.
	 *
	 * @return RdfWriter $this
	 */
	public function say( $verb );

	/**
	 * Emits a resource object.
	 * Must be preceded by a call to predicate().
	 *
	 * @param string $object a resource reference (URI or QName)
	 *
	 * @return RdfWriter $this
	 */
	public function is( $object );

	/**
	 * Emits a text object.
	 * Must be preceded by a call to predicate().
	 *
	 * @param string $text the text to be writeted
	 * @param string|null $language the language the text is in
	 *
	 * @return RdfWriter $this
	 */
	public function text( $text, $language = null );


	/**
	 * Emits a text object.
	 * Must be preceded by a call to predicate().
	 *
	 * @param string $literal the value encoded as a string
	 * @param string|null $type a resource reference (URI or QName)
	 *
	 * @return RdfWriter $this
	 */
	public function value( $literal, $type = null );

	/**
	 * Shorthand for say( 'a' )->is( $type ).
	 *
	 * @param string $type a resource reference (URI or QName)
	 *
	 * @return RdfWriter $this
	 */
	public function a( $type );

	/**
	 * Returns a document-level sub-writer.
	 *
	 * @note: do not call drain() on sub-writers!
	 *
	 * @return RdfWriter
	 */
	public function sub();

	/**
	 * Resets any state the writer may be holding.
	 */
	public function reset();

	/**
	 * @return string a MIME type
	 */
	public function getMimeType();
}
