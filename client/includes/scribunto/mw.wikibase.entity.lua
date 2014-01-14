--[[
	Registers and defines functions to handle Wikibase Entities through the Scribunto extension.

	@since 0.5

	@license GNU GPL v2+
	@author Marius Hoch < hoo@online.de >
]]

local php = mw_interface
local entity = {}
local metatable = {}
local methodtable = {}

metatable.__index = methodtable

-- Create new entity object from given data
--
-- @param data
entity.create = function( data )
	if type( data ) ~= 'table' then
		error( 'The given entity data must be a table' )
	end

	local entity = data
	setmetatable( entity, metatable )

	return entity
end

-- Get the label for a given language code
--
-- @param code
methodtable.getLabel = function( entity, code )
	local code = code or mw.language.getContentLanguage():getCode()

	if code == nil then
		return nil
	end

	if entity.labels == nil then
		return nil
	end

	local label = entity.labels[code]

	if label == nil then
		return nil
	end

	return label.value
end

-- Get the sitelink title linking to the given site id
--
-- @param globalSiteId
methodtable.getSitelink = function( entity, globalSiteId )
	if entity.sitelinks == nil then
		return nil
	end

	globalSiteId = globalSiteId or php.getGlobalSiteId()

	if globalSiteId == nil then
		return nil
	end

	local sitelink = entity.sitelinks[globalSiteId]

	if sitelink == nil then
		return nil
	end

	return sitelink.title
end

-- Get a table with all property ids attached to the entity.
methodtable.getProperties = function( entity )
	if entity.claims == nil then
		return {}
	end

	-- Get the keys (property ids)
	local properties = {}

	local n = 0
	for k, v in pairs( entity.claims ) do
		if string.match( k, '^%u%d+' ) ~= nil then
			n = n + 1
			properties[n] = k
		end
	end

	return properties
end

methodtable.formatPropertyValues = function( entity, propertyId )
	local formatted = php.formatPropertyValues(
		entity.id,
		propertyId
	)

	return {
		value = formatted,
		label = mw.wikibase.label( propertyId )
	}
end

mw.wikibase.entity = entity
package.loaded['mw.wikibase.entity'] = entity
mw_interface = nil

return entity
