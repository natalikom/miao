import os
import xml.etree.ElementTree as etree

class FileNotFound(Exception):
	pass
class XmlParseError(Exception):
	pass
class PropertyNotFound(Exception):
	pass

class Config:
	'Класс для работы с xml деревом'
	
	_tree = None 
	def __init__(self, config_file):
		try:
			self._tree = etree.parse(config_file)
		except etree.ParseError:
			raise XmlParseError('Config file "%s" is invalid xml' % config_file)	

	def get(self, path):	
		node = self._tree.getroot().find(path)
		if node is None:
			raise PropertyNotFound('Property "%s" not found in "%s"' % (path, config_file))
		return node.text