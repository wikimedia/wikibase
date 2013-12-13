--[[
    Registers and defines functions to access Wikibase through the Scribunto extension
    Provides Lua setupInterface

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
    http://www.gnu.org/copyleft/gpl.html

    @since 0.4

    @licence GNU GPL v2+
    @author Jens Ohlig < jens.ohlig@wikimedia.de >
]]

local wikibase = {}

function wikibase.setupInterface()
	local php = mw_interface
	mw_interface = nil

	wikibase.getEntity = function()
		local id = php.getEntityId( tostring( mw.title.getCurrentTitle().prefixedText ) )
		if id == nil then return nil end
		local entity = php.getEntity( id )
		return entity
	end

	wikibase.label = function( id )
		local code = mw.language.getContentLanguage():getCode()
		if code == nil then return nil end
		local entity = php.getEntity( id )
		if entity == nil or entity.labels == nil then return nil end
		local label = entity.labels[code]
		if label == nil then return nil end
		return label.value
	end

	wikibase.sitelink = function( id )
		local entity = php.getEntity( id )
		if entity == nil or entity.sitelinks == nil then return nil end
		local globalSiteId = php.getGlobalSiteId()
		if globalSiteId == nil then return nil end
		local sitelink = entity.sitelinks[globalSiteId]
		if sitelink == nil then return nil end
		return sitelink.title
	end

<<<<<<< HEAD
	wikibase.formattedPropertyValues = function()
		local entity = wikibase.getEntity()
		-- Get the keys, i.e. property names
		local properties = {}
		local n = 0
		if entity.claims == nil then return {} end
		for k, v in pairs( entity.claims ) do
			n = n + 1
			properties[n] = k
		end
		-- Filter out all properties that don't start with a capital letter
		local filter = function( func, xs )
			local table = {}
			for i, v in pairs( xs ) do
				if func( v ) then
					table[i] = v
				end
			end
			return table
		end
		local is_capital_property = function( x )
			return ( string.match( x, '^%u%d+' ) ~= nil )
		end
		properties = filter( is_capital_property, properties )
		-- Build the properties table to be returned
		n = 0
		local p = {}
		for k, v in pairs( properties ) do
			n = n + 1
			p[n] = v
		end
		properties = p
		p = {}
		for i, v in pairs( properties ) do
			p[v] = { ["label"] = wikibase.label( v ) }
		end
		return p
	end
=======
  wikibase.properties = function ()
    local entity = {}
    entity = wikibase.getEntity()
    -- Get the keys, i.e. property names
    local properties = {}
    local n = 0
    if entity.claims == nil then return {} end
    for k,v in pairs( entity.claims ) do
      n = n+1
      properties[n]=k
    end
    -- Filter out all properties that don't start with a capital letter
    local filter = function( func, xs )
         local table= {}
         for i,v in pairs( xs ) do
             if func( v ) then
                table[i]=v
             end
         end
         return table
     end
     local is_capital_property = function( x )
        return ( string.match( x, '^%u%d+' ) ~= nil )
     end
     properties = filter( is_capital_property, properties )
     -- Build the properties table to be returned
     n = 0
     local p = {}
     for k,v in pairs( properties ) do
           n=n+1
           p[n]=v
      end
      properties = p
      p = {}
      for i,v in pairs( properties ) do
        formattedProperty = php.renderForEntityId( php.getEntityId( tostring( mw.title.getCurrentTitle().prefixedText ) ), v )
        p[v] = { ["value"] = tostring( formattedProperty ), ["label"] = wikibase.label( v ) }
        -- ["label"] = wikibase.label( v ),
      end
      return p
  end
>>>>>>> Implementation of snakformatted property table in Lua

	mw = mw or {}
	mw.wikibase = wikibase
	package.loaded['mw.wikibase'] = wikibase
	wikibase.setupInterface = nil
end

return wikibase
