require 'sass'
require 'base64'
require 'cgi'

module Sass::Script::Functions
    def base64Encode(string)
        assert_type string, :String
        Sass::Script::String.new(Base64.encode64(string.value))
    end
    declare :base64Encode, :args => [:string]

    def svgEncode(string)
        svg = string.value
        encoded_svg = CGI::escape(svg).gsub('+', '%20')
        data_url = "url('data:image/svg+xml;charset=utf-8," + encoded_svg + "')"
        Sass::Script::String.new(data_url)
    end
    declare :svgEncode, :args => [:string]

end
