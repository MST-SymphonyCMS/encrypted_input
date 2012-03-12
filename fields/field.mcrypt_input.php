<?php
	
	Class fieldMcrypt_Input extends Field {
		
		function encrypt($string) { 
                return trim(
                    base64_encode(
                        mcrypt_encrypt(
                            MCRYPT_RIJNDAEL_256,
                            hash('sha256', Symphony::Configuration()->get('salt', 'mcrypt_input'), TRUE),
                            $string,
                            MCRYPT_MODE_ECB,
                            mcrypt_create_iv(
                                mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB),
                                MCRYPT_RAND
                            )
                        )
                    )
                ); 
            } 

            function decrypt($string) { 
                return trim(
                    mcrypt_decrypt(
                        MCRYPT_RIJNDAEL_256, 
                        hash('sha256', Symphony::Configuration()->get('salt', 'mcrypt_input'), TRUE),
                        base64_decode($string), 
                        MCRYPT_MODE_ECB, 
                        mcrypt_create_iv(
                            mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB),
                            MCRYPT_RAND
                        )
                    )
                ); 
            }
		
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Mcrypt Input';
			$this->_required = TRUE;
			$this->set('required', 'yes');
		}
		
		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');
			if($id === false) return false;

			$fields = array();
			$fields['field_id'] = $id;

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}
		
		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL, $entry_id = null){
		    
		    Administration::instance()->Page->addScriptToHead(URL . '/extensions/mcrypt_input/assets/mcrypt_input.publish.js', 100);
		    
			$value = General::sanitize($data['value']);
			$label = Widget::Label($this->get('label'));
			
			if(empty($value)) {
			    if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));
			    $label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? $value : NULL)));
			    if($flagWithError != NULL) {
			        $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			    } else {
			        $wrapper->appendChild($label);
			    }
			} else {
			    $label->appendChild(new XMLElement('span', $value, array('class' => 'frame')));
			    $label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, 'encrypted:' . $data['value'], 'hidden'));
			    $wrapper->appendChild($label);
			}
			
		}
		
		function appendFormattedElement(&$wrapper, $data, $encode=FALSE, $mode=NULL, $entry_id=NULL){
			if(!is_array($data) || empty($data)) return;
			
			$value = $this->decrypt($data['value']);
			
			$xml = new XMLElement($this->get('element_name'), $value);
			$wrapper->appendChild($xml);
		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=null) {
			$status = self::__OK__;
			
			// has already been encrypted
			if(preg_match("/^encrypted:/", $data)) {
			    return array(
    				'value' => preg_replace("/^encrypted:/", '', $data),
    			);
			}
			else {
			    return array(
    				'value' => $this->encrypt($data),
    			);
			}
			
		}

	}