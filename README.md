asasd
# K-Forum

* K-Forum is short for "Knowledge Forum".
* WordPress Version 4.3.3 and above

# Installation

* K-Forum works only on "Nice URI"


# todo

    - font awesome 이 캐시를 할 때 문제된다.
    
    - Support WordPress version 3.5 and above

    - K-FORUM 에 키워드를 3개까지 둘 수 있도록 할 것.

    	-- 해보니 키워드 3개는 진짜 어렵다.
    	키워드를 그냥 바뀌가면서 테스트 하도록 한다.

    - 키워드가 더 많이 들어가도록 할 것. 검색이 잘 안된다.

    - 사진을 내용에 따라서 2개 이상을 넣도록 할 것.

    	300 글자 이내이면 사진 1개, 300글자 이상이면 사진 2개 필수.

    - OG 태그와 메타 태그 를 추가 할 것
    - Add OG Tags
    - User Google API v3 to communicate with blogger.
    

    - primary 사진 업로드 기능을 추가 할 것. 그래서 글 목록이나 OG 태그 등에 사용 할 것. 단, 글 내용에는 안보임.
    	-- 생략되면 처번째 이미지가 자동으로 대표 사진이 됨.



    - 웹브라우저 제목에 글 제목이 나오게 할 것.
    
    - Add philgo banners on template



# Overview

* k-forum manages its post and category data exactly the way how WordPress does.

    * Which means you can manage k-forum data without k-forum plugin
    
    simply because all the k-forum data is stored in the sample place of WordPress post/category/meta table
     
    and be managed by WordPress default Post/Category menu.
     

* The way how k-forum work is the way how WordPress work.

    * When you write a post under a k-forum category which is a child of another k-forum category,
    
    the post will be shown not only its category but also is shown under its parent category.
    
    And this is how WordPres works.
    
# Post

* Post relation to forum

    * There are "Category A" and "Category B" and "Category B" is under " Category A"
    
    * You write a post - "No. 1" under "Category B".
    
    * "No. 1" will be listed under "Category B" and "Category A".
    
    * When you view "No. 1" under "Category A" list, the category of "No. 1" is "Category B" not "Category A".
    




# Bug

* The links of images of Media panel in admin page are broken. they are all xbox.


* fixed : Adding permission on edit/delete. ( May 9, 2016 )


# Routing

    * "/forum/forum-name" for list
    * "/forum/forum-name/edit" for new post
    * "/forum/post-number/edit" for editing a post
    * "/forum/forum-name/post-number" for reading ( view ) a post


# Template

    * All templates can be overridden by creating same file name under theme folder.
    
    * Each forum can have a different style of design by setting template postfix on admin page.
    


If you put postfix as 'abc' on ABC forum and you want to see a post of the forum.

	"theme/name/forum-view-abc.php" file will be used if it exists.

	"theme/name/forum-view-basic.php" file will be used if the file above does not exists.

	"plugin/k-forum/template/forum-view-basic.php" file will be used if none of the above file exists. 









# SEO
    * 블로그를 목록하고 블로그 포스팅 선택 할 수 있도록 한다.
    * OG 태그 직접 입력.
        * OG 타이틀 이미지 직접 선택. 업로드.
	* http://static.googleusercontent.com/media/www.google.co.kr/ko/kr/intl/ko/webmasters/docs/search-engine-optimization-starter-guide-ko.pdf
	* https://docs.google.com/document/d/1l5nBwK4ztgml0kUXvy73cmMroDfWifooddutvfk_tCU/edit
	* 키워드 사용 회수 카운트.
	* A 태그 한개 이상 삽입.
	
	
	
	
* Comment work
	* Comment list. ( need to create a whole comment function to display buttons )
	* Comment Create with file upload. ( need to create a whole comment function ) 
	* Comment Update with file upload. 

* File Uploading.

    - show images in media page properly.

* Post/Comment Voting.

* Forum management
	* forum category statistics
	* forum options
		* how many post will be listed in list view page.
		* templating for each forum category.
		* and other options.
        

# K Forum 테스트 하는 방법

$ curl "http://work.org/wordpress/?test=k-forum"




# Features

* K-Forum uses the same idea of managing posts and comments exactly as how WordPress does. Which means even if you uninstall K-Forum plugin, you can still use the contents that were written by K-Forum.
* K-Forum lets theme developers to use k-forum template files.  



 

## K Forum Delete

When a k-forum category is deleted, it renames the category as "Deleted-[Original Forum Name]" and puts it out of the k-forum category.

We do it just because the user may delete a category mistakenly. If the user wants to delete the categor, he can delete it from the "Post" ==> "Category Menu".


## K Forum URL

* /fourm/qna ==> qna forum.
* /forum/qna/edit ==> new post form page under qna forum
* /qna ==> qna fourm. This will be redirected to /forum/qna. If bbPress has same slug, bbPress will take priority. So, bbPress qna forum will be display.


* 만약 글을 k forum 에서 작성했는데, Post 메뉴에서 글을 수정 할 때, 카테고리를 여러개 선택한다면,

    - 글 하나에 여러개의 카테고리가 선택된다.
    - 하지만 K forum 은 글 하나에 하나의 k forum 카테고리만 가지는 것으로 가정하기 때문에 올바르지 않은 결과가 나타날 수 있다.
    - 따라서 글 수정은 Post 메뉴에서 하지 않는다.



# K-Forum Internals

## Much the same as WordPress

For instance, if a k-forum comment is deleted, it saves into trash just like WordPress comment does.


# 설치

Single Site 와 Multi Site 에서 플러그인을 활성화 해야한다.

    add_action( 'template_redirect', function() {
        if ( ! defined('K_FORUM') ) {
            echo "Enable k-forum";
            exit;
        }
    });


# 게시판 링크

게시판을 관리자 페이지에서 생성하고 난 다음

<a href="<?php home_url()?>/forum/qna">
<a href="<?php home_url()?>/forum/faq">

와 같이 사용 할 수 있다.


# ETC

* 글 카테고리 메뉴에서 K-forum 을 삭제해도 다시 생성된다.

* 워드프레스의 글과 카테고리를 그대로 활용하므로

    * 글과 동작이 매우 유사하다.
    
        * 예를 들면, 최근 글을 목록하는 페이지가 있다면, 케이 포럼에 쓴 글이 나타난다. 
     
    * 글 및 카테고리 관리를 글 메뉴와 카테고리 메뉴에서 하면 된다.
    
        * 예를 들면, 카테고리 별 글 이동 같은 것이 있다.



# SEO

## 동작

SEO 를 했다면, 계속 SEO 를 하도록 쿠키에 저장하고, SEO 버튼을 닫았으면, 엮시 기억해서 SEO 를 펴쳐보이지 않도록 한다. 즉, 마지막 기억을 한다.
 

## 키워드

키워드를 bar 로 했을 경우, 영어에서 bar 와 bargain 은 완전히 다른 의미이다.
따라서 영어로하는 경우에는 키워드 매칭을 단어별로 분리를 해야 한다.
하지만 키워드를 '노래'로 했을 경우, 한국어에서 "노래"와 "노래하다"는 비슷한 의미로 가진다.
따라서 글 내용이 한글인 경우, 글 내용 비례하여 키워드 밀집도를 다지는 경우, 키워드를 단어별로 분리하지 않고 그냥 단어 속에 키워드가 포함되면 같이 카운트를 해야 한다.
 * 따라서 키워드는 최소한 한글 두자 이상이 되어야 한다.
 * 참고로 자바스크립트에서 한글 한 글자의 길이는  1 이다.



# XMLRPC

It is amazing that xmlrpc for php is still working and maintained actively.

https://github.com/gggeek/phpxmlrpc
http://gggeek.github.io/phpxmlrpc/
https://github.com/gggeek/phpxmlrpc/blob/master/doc/manual/phpxmlrpc_manual.adoc

github.com/thruthesky/x 의 etc/service/push_to_blog.php 를 열어서 기존의 코드를 네이버로 집어 넣는다.

완전한 별도의 플러그인으로 하나 더 만든다.

https://docs.google.com/document/d/1qjb7JBeGBh-VWFFRJxeJwGTSGo1SvtuvXSUJ75Hzl3g/edit#heading=h.cjwku3clyqxh



## 블로깅 동작 방식

* 관리자 설정에서 '블로그 설정'을 하지 않으면 자동으로 블로그 포스팅 옵션이 나타나지 않는다.
* 관리자 설정에서 '관리자만 블로그 등록 가능'에 체크하면, 관리자만 블로그 포스팅 할 수 있다.
* 글 쓰기 및 수정을 할 때, '블로그'를 선택해야 한다.
    * 처음 글 쓰기 할 때, 블로그를 선택하지 않고, 글 수정 할 때, 블로그를 선택해도 블로그에 글이 생성된다.
* 글 수정 시, '블로그 선택 옵션'에 글이 이미 등록된 블로그에는 체크가 미리된다.

