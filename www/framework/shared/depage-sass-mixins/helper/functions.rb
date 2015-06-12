require 'sass'
require 'base64'

module Sass::Script::Functions
    def base64Encode(string)
        assert_type string, :String
        Sass::Script::String.new(Base64.strict_encode64(string.value))
    end
    declare :base64Encode, :args => [:string]

    def svgEncode(string)
        assert_type string, :String
        data_url = "url(\"data:image/svg+xml;charset=utf-8;base64," + Base64.strict_encode64(string.value) + "\")"
        Sass::Script::String.new(data_url)
    end
    declare :svgEncode, :args => [:string]

end
