<?php


// XML


/**
 * Add XML element (with specified name, attributes and children tags)
 *
 * @param object $xml Parent element
 * @param string $name Element name
 * @param array $data ('attrz' - attributes, optional; 'text' - text, optional; 'tagz' - children elements/tags, optional;)
 *
 * @return object $xml_element New element
 */
function xml_element_add($xml, $name, $data=null) {

    $xml_element = $xml->createElement($name, ((isset($data['text']) ? $data['text'] : null)));

    if (isset($data['attrz'])) {
        foreach($data['attrz'] as $k => $v) {
            $xml_element->setAttribute($k, $v);
        }
    }

    if (isset($data['tagz'])) {
        foreach($data['tagz'] as $k => $v) {
            $xml_element->appendChild($xml->createElement($k, $v));
        }
    }

    return $xml_element;
}




/**
 * Read all attributes of the specified XML element
 *
 * @param object $el XML element
 *
 * @return array|void $r Attributes
 */
function xml_element_get_attributes($el) {

    if ($el->hasAttributes()) {

        $attrz = $el->attributes;

        foreach ($attrz as $attr) {
            $r[$attr->name] = $attr->value;
        }
    }

    return @$r;
}






/**
 * Compare old and new version of a file, then replace if they differ, or delete new version if not
 *
 * @param string $fileold Old version
 * @param string $filenew New version
 *
 * @return void
 */
function file_old_new($fileold, $filenew) {

    if (file_exists($fileold)) {

        $sha_old = sha1_file($fileold);
        $sha_new = sha1_file($filenew);

        if ($sha_old==$sha_new) {
            unlink($filenew);
            return;
        }
    }

    rename($filenew, $fileold);
}












/**
 * Read epg XML
 *
 * @param string $dateair DateAir
 * @param int $chnl ChannelID
 *
 * @return array $epg EPG data (attributes + items)
 */
function epgxml_reader($dateair, $chnl) {

    global $cfg;

    $xml = new DOMDocument();

    $xml->load($cfg[SCTN]['epgxml_path'].sprintf('%u-%s.xml', $chnl, $dateair));

    $epg['attrz'] = xml_element_get_attributes($xml->getElementsByTagName('EPG')->item(0));

    $element = $xml->documentElement;

    foreach ($element->childNodes as $item) {

        if ($item->childNodes) {

            $epg_row = array();

            foreach ($item->childNodes as $data) {

                if ($data->nodeType==XML_ELEMENT_NODE) {
                    $epg_row[$data->nodeName] = $data->nodeValue;
                }
            }

            $epg['itemz'][] = $epg_row;
        }
    }

    return $epg;
}




